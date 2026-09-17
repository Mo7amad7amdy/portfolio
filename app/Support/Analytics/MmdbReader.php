<?php

namespace App\Support\Analytics;

use RuntimeException;

/**
 * Minimal, dependency-free reader for MaxMind DB (.mmdb) files — works with
 * DB-IP "IP to City Lite" and MaxMind GeoLite2-City. Read-only, pure PHP.
 *
 * Spec: https://maxmind.github.io/MaxMind-DB/
 */
class MmdbReader
{
    private const METADATA_MARKER = "\xAB\xCD\xEFMaxMind.com";

    /** @var resource */
    private $fh;

    private int $nodeCount;

    private int $recordSize;

    private int $ipVersion;

    private int $nodeBytes;

    private int $treeSize;

    private int $dataStart;

    private int $ipv4Start = -1;

    private array $metadata;

    public function __construct(string $path)
    {
        $fh = @fopen($path, 'rb');
        if (! $fh) {
            throw new RuntimeException("Cannot open GeoIP database: $path");
        }
        $this->fh = $fh;

        $size = fstat($fh)['size'];
        $tailLen = min($size, 128 * 1024);
        fseek($fh, $size - $tailLen);
        $tail = fread($fh, $tailLen);
        $pos = strrpos($tail, self::METADATA_MARKER);
        if ($pos === false) {
            throw new RuntimeException('Not a MaxMind DB file.');
        }
        $metaStart = $size - $tailLen + $pos + strlen(self::METADATA_MARKER);

        [$this->metadata] = $this->decode($metaStart, $metaStart);
        $this->nodeCount = (int) $this->metadata['node_count'];
        $this->recordSize = (int) $this->metadata['record_size'];
        $this->ipVersion = (int) $this->metadata['ip_version'];
        if (! in_array($this->recordSize, [24, 28, 32], true)) {
            throw new RuntimeException("Unsupported record size {$this->recordSize}.");
        }
        $this->nodeBytes = intdiv($this->recordSize * 2, 8);
        $this->treeSize = $this->nodeBytes * $this->nodeCount;
        $this->dataStart = $this->treeSize + 16;
    }

    public function __destruct()
    {
        if (is_resource($this->fh)) {
            fclose($this->fh);
        }
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    /** @return array<string, mixed>|null */
    public function get(string $ip): ?array
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }
        $bitCount = strlen($packed) * 8;

        if ($bitCount === 128 && $this->ipVersion === 4) {
            return null;
        }

        $node = 0;
        if ($bitCount === 32 && $this->ipVersion === 6) {
            $node = $this->ipv4StartNode();
        }

        for ($i = 0; $i < $bitCount && $node < $this->nodeCount; $i++) {
            $bit = (ord($packed[$i >> 3]) >> (7 - ($i % 8))) & 1;
            $node = $this->readRecord($node, $bit);
        }

        if ($node <= $this->nodeCount) {
            return null; // == nodeCount means "no data"
        }
        $offset = $node - $this->nodeCount + $this->treeSize;
        [$value] = $this->decode($offset, $this->dataStart);

        return is_array($value) ? $value : null;
    }

    private function ipv4StartNode(): int
    {
        if ($this->ipv4Start >= 0) {
            return $this->ipv4Start;
        }
        $node = 0;
        for ($i = 0; $i < 96 && $node < $this->nodeCount; $i++) {
            $node = $this->readRecord($node, 0);
        }

        return $this->ipv4Start = $node;
    }

    private function readRecord(int $node, int $index): int
    {
        fseek($this->fh, $node * $this->nodeBytes);
        $b = fread($this->fh, $this->nodeBytes);

        switch ($this->recordSize) {
            case 24:
                $o = $index * 3;

                return (ord($b[$o]) << 16) | (ord($b[$o + 1]) << 8) | ord($b[$o + 2]);
            case 28:
                if ($index === 0) {
                    return ((ord($b[3]) & 0xF0) << 20) | (ord($b[0]) << 16) | (ord($b[1]) << 8) | ord($b[2]);
                }

                return ((ord($b[3]) & 0x0F) << 24) | (ord($b[4]) << 16) | (ord($b[5]) << 8) | ord($b[6]);
            default: // 32
                return unpack('N', substr($b, $index * 4, 4))[1];
        }
    }

    private function read(int $offset, int $length): string
    {
        if ($length === 0) {
            return '';
        }
        fseek($this->fh, $offset);

        return fread($this->fh, $length);
    }

    /** @return array{0: mixed, 1: int} value and next offset */
    private function decode(int $offset, int $base)
    {
        $ctrl = ord($this->read($offset, 1));
        $offset++;
        $type = $ctrl >> 5;

        if ($type === 1) { // pointer
            $ss = ($ctrl >> 3) & 0x3;
            $vvv = $ctrl & 0x7;
            $bytes = $this->read($offset, $ss + 1);
            $offset += $ss + 1;
            $ptr = match ($ss) {
                0 => ($vvv << 8) | ord($bytes[0]),
                1 => (($vvv << 16) | (ord($bytes[0]) << 8) | ord($bytes[1])) + 2048,
                2 => (($vvv << 24) | (ord($bytes[0]) << 16) | (ord($bytes[1]) << 8) | ord($bytes[2])) + 526336,
                default => unpack('N', $bytes)[1],
            };
            [$value] = $this->decode($base + $ptr, $base);

            return [$value, $offset];
        }

        if ($type === 0) { // extended
            $type = 7 + ord($this->read($offset, 1));
            $offset++;
        }

        $size = $ctrl & 0x1F;
        if ($size >= 29) {
            $extra = $size - 28;
            $b = $this->read($offset, $extra);
            $offset += $extra;
            $size = match ($extra) {
                1 => 29 + ord($b[0]),
                2 => 285 + ((ord($b[0]) << 8) | ord($b[1])),
                default => 65821 + ((ord($b[0]) << 16) | (ord($b[1]) << 8) | ord($b[2])),
            };
        }

        switch ($type) {
            case 2: // utf8 string
            case 4: // bytes
                return [$this->read($offset, $size), $offset + $size];
            case 3: // double
                return [unpack('E', $this->read($offset, 8))[1], $offset + 8];
            case 15: // float
                return [unpack('G', $this->read($offset, 4))[1], $offset + 4];
            case 5: // uint16
            case 6: // uint32
            case 9: // uint64
            case 10: // uint128
                return [$this->uint($this->read($offset, $size)), $offset + $size];
            case 8: // int32
                $v = $this->uint($this->read($offset, $size));
                if ($size === 4 && $v >= 0x80000000) {
                    $v -= 0x100000000;
                }

                return [$v, $offset + $size];
            case 7: // map
                $map = [];
                for ($i = 0; $i < $size; $i++) {
                    [$key, $offset] = $this->decode($offset, $base);
                    [$map[$key], $offset] = $this->decode($offset, $base);
                }

                return [$map, $offset];
            case 11: // array
                $arr = [];
                for ($i = 0; $i < $size; $i++) {
                    [$arr[], $offset] = $this->decode($offset, $base);
                }

                return [$arr, $offset];
            case 14: // boolean (value in size)
                return [$size !== 0, $offset];
            case 12: // data cache container
            case 13: // end marker
                return [null, $offset];
            default:
                throw new RuntimeException("Unknown MMDB data type $type.");
        }
    }

    private function uint(string $bytes): int|string
    {
        $v = 0;
        $len = strlen($bytes);
        if ($len > 7) { // may overflow PHP int: return as decimal string
            $hex = bin2hex($bytes);

            return function_exists('gmp_init') ? gmp_strval(gmp_init($hex, 16)) : (string) hexdec($hex);
        }
        for ($i = 0; $i < $len; $i++) {
            $v = ($v << 8) | ord($bytes[$i]);
        }

        return $v;
    }
}
