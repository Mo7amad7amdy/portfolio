"""
Build the "living portrait" assets for the portfolio hero from one photo.

    pip install rembg onnxruntime opencv-python pillow numpy
    python tools/portrait/make_portrait.py path/to/photo.jpg

Writes to public/images/portrait/:
    portrait.webp        enhanced, background-removed cut-out
    portrait-depth.png   Depth Anything V2 depth map (white = near)
    meta.json            eye + head positions (paste into the dashboard if needed)
and public/images/profile.jpg (studio headshot used in the About section).

Then either run `php artisan migrate` on a fresh install, or upload the two images in
Dashboard → Profile & Photo → Living portrait and click the eyes on the preview.
"""
import json
import sys
import urllib.request
from pathlib import Path

import cv2
import numpy as np
from PIL import Image

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / "public" / "images" / "portrait"
MODEL_URL = "https://github.com/fabio-sim/Depth-Anything-ONNX/releases/download/v2.0.0/depth_anything_v2_vits.onnx"
MODEL = Path(__file__).with_name("depth_anything_v2_vits.onnx")


def enhance(bgr: np.ndarray) -> np.ndarray:
    """Studio polish: gentle local contrast, less orange cast, split-tone, highlight roll-off, light sharpening."""
    lab = cv2.cvtColor(bgr, cv2.COLOR_BGR2LAB)
    L, A, B = cv2.split(lab)
    L = cv2.createCLAHE(clipLimit=1.1, tileGridSize=(8, 8)).apply(L)
    A = (128 + (A.astype(np.float32) - 128) * 0.96).clip(0, 255).astype(np.uint8)
    B = (128 + (B.astype(np.float32) - 128) * 0.90).clip(0, 255).astype(np.uint8)
    img = cv2.cvtColor(cv2.merge([L, A, B]), cv2.COLOR_LAB2BGR).astype(np.float32) / 255

    lum = img.mean(axis=2, keepdims=True)
    img += np.clip(1 - lum * 2.2, 0, 1) * np.array([0.045, 0.02, -0.02])   # teal shadows
    img += np.clip((lum - 0.55) * 2.2, 0, 1) * np.array([-0.03, 0.01, 0.035])  # warm highlights
    img = np.clip(img, 0, 1)
    img = np.where(img > 0.8, 0.8 + (img - 0.8) * 0.6, img)
    img = np.clip(img + 0.05 * (img - 0.5) * (1 - np.abs(img - 0.5) * 2), 0, 1)

    u8 = (img * 255).astype(np.uint8)
    return cv2.addWeighted(u8, 1.35, cv2.GaussianBlur(u8, (0, 0), 1.0), -0.35, 0)


def cutout_alpha(rgb: np.ndarray) -> np.ndarray:
    from rembg import new_session, remove
    out = remove(Image.fromarray(rgb), session=new_session("u2net_human_seg"))
    return np.array(out)[..., 3].astype(np.float32) / 255


def depth_map(rgb: np.ndarray, alpha: np.ndarray) -> np.ndarray:
    import onnxruntime as ort
    if not MODEL.exists():
        print("Downloading Depth Anything V2 (~100 MB)...")
        urllib.request.urlretrieve(MODEL_URL, MODEL)
    h, w = rgb.shape[:2]
    x = cv2.resize(rgb, (518, 518), interpolation=cv2.INTER_CUBIC).astype(np.float32) / 255
    x = ((x - [0.485, 0.456, 0.406]) / [0.229, 0.224, 0.225]).transpose(2, 0, 1)[None].astype(np.float32)
    sess = ort.InferenceSession(str(MODEL))
    d = sess.run(None, {sess.get_inputs()[0].name: x})[0][0]
    d = cv2.resize(d, (w, h), interpolation=cv2.INTER_CUBIC)
    fg = d[alpha > 0.5]
    lo, hi = np.percentile(fg, 2), np.percentile(fg, 99.5)
    d = np.clip((d - lo) / (hi - lo), 0, 1) * 0.85 + 0.15
    d = d * (alpha > 0.5)
    grown = cv2.dilate(d, np.ones((3, 3), np.uint8), iterations=12)   # stretch edges instead of tearing
    d = np.where(alpha > 0.5, d, grown * 0.9)
    return cv2.GaussianBlur(d, (0, 0), 3)


def find_eyes(rgb: np.ndarray):
    gray = cv2.cvtColor(rgb, cv2.COLOR_RGB2GRAY)
    faces = cv2.CascadeClassifier(cv2.data.haarcascades + "haarcascade_frontalface_default.xml") \
        .detectMultiScale(gray, 1.1, 5, minSize=(120, 120))
    if len(faces) == 0:
        return None, None
    fx, fy, fw, fh = max(faces, key=lambda f: f[2] * f[3])
    roi = gray[fy:fy + fh // 2 + fh // 10, fx:fx + fw]
    eyes = cv2.CascadeClassifier(cv2.data.haarcascades + "haarcascade_eye.xml") \
        .detectMultiScale(roi, 1.05, 6, minSize=(fw // 10, fw // 10))
    eyes = sorted(eyes, key=lambda e: e[2] * e[3], reverse=True)[:2]
    if len(eyes) < 2:
        return (fx, fy, fw, fh), None
    pts = sorted((fx + x + ew / 2, fy + y + eh / 2) for x, y, ew, eh in eyes)
    return (fx, fy, fw, fh), pts


def main(src_path: str) -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    bgr = cv2.imread(src_path)
    if bgr is None:
        sys.exit(f"Cannot read {src_path}")
    rgb_src = cv2.cvtColor(bgr, cv2.COLOR_BGR2RGB)
    H, W = rgb_src.shape[:2]

    print("Removing background...")
    alpha = cutout_alpha(rgb_src)
    print("Enhancing...")
    rgb = cv2.cvtColor(enhance(bgr), cv2.COLOR_BGR2RGB).astype(np.float32)

    # warm rim light, stronger at the top, and soft side edges
    a8 = (alpha * 255).astype(np.uint8)
    inner = cv2.GaussianBlur(cv2.erode(a8, np.ones((3, 3), np.uint8), iterations=6), (0, 0), 6) / 255
    rim = (np.clip(alpha - inner, 0, 1) * np.linspace(1, 0, H)[:, None] ** 1.5)[..., None]
    rgb = np.clip(rgb + rim * np.array([255, 190, 90]) * 0.32, 0, 255)
    xs = np.linspace(0, 1, W)
    a = alpha * np.clip(np.minimum(xs, 1 - xs) / 0.06, 0, 1)[None, :]
    rgba = np.dstack([rgb, a * 255]).astype(np.uint8)
    size = (1000, round(H * 1000 / W))
    Image.fromarray(rgba).resize(size, Image.LANCZOS).save(OUT / "portrait.webp", quality=90, method=6)

    print("Estimating depth...")
    d = depth_map(rgb_src, alpha)
    Image.fromarray((d * 255).astype(np.uint8)).resize((500, round(H * 500 / W)), Image.BICUBIC) \
        .save(OUT / "portrait-depth.png", optimize=True)

    face, eyes = find_eyes(rgb_src)
    meta = {"version": 1, "focus": round(float(np.percentile(d[alpha > 0.5], 35)), 3)}
    if eyes:
        dist = eyes[1][0] - eyes[0][0]
        meta["eyes"] = [{"x": round(x / W, 4), "y": round(y / H, 4),
                         "rx": round(dist * 0.25 / W, 4), "ry": round(dist * 0.095 / H, 4)} for x, y in eyes]
    else:
        print("!! Eyes not detected - click them in the dashboard eye picker.")
    if face is not None:
        fx, fy, fw, fh = face
        meta["head"] = {"x": round((fx + fw / 2) / W, 4), "y": round((fy + fh * 0.43) / H, 4),
                        "rx": round(fw * 0.58 / W, 4), "ry": round(fh * 0.82 / H, 4)}
    (OUT / "meta.json").write_text(json.dumps(meta, indent=1))

    # studio headshot for the About card
    yy, xx = np.mgrid[0:H, 0:W]
    glow = np.clip(1 - np.sqrt(((xx - W * .52) / (W * .75)) ** 2 + ((yy - H * .3) / (H * .6)) ** 2), 0, 1) ** 1.6
    bg = np.array([22, 17, 12]) + glow[..., None] * np.array([190, 110, 30])
    comp = rgba[..., :3] * (a[..., None]) + bg * (1 - a[..., None])
    Image.fromarray(np.clip(comp, 0, 255).astype(np.uint8)).resize((800, round(H * 800 / W)), Image.LANCZOS) \
        .save(ROOT / "public" / "images" / "profile.jpg", quality=88)

    print(f"Done -> {OUT}\n{json.dumps(meta)}")


if __name__ == "__main__":
    if len(sys.argv) != 2:
        sys.exit(__doc__)
    main(sys.argv[1])
