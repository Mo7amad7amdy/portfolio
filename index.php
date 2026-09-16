<?php

/*
 * Front controller for running the app from the project root (no "/public" in the URL).
 * Laravel detects the base path from this file's location, so routes, asset() and
 * redirects all resolve to e.g. http://localhost/portfolio/... automatically.
 * The real bootstrap lives in public/index.php.
 */
require __DIR__.'/public/index.php';
