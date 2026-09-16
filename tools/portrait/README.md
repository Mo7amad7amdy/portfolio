# Living portrait generator

Turns one photo into the hero's animated "AI photo" (3D head turn, blinking, breathing, moving light).

```bash
pip install rembg onnxruntime opencv-python pillow numpy
python tools/portrait/make_portrait.py path/to/photo.jpg
```

Best input: front-facing, eyes visible, head and shoulders, at least 1000 px wide.

Output goes to `public/images/portrait/` (`portrait.webp`, `portrait-depth.png`, `portrait-closed.webp`, `meta.json`) plus
`public/images/profile.jpg`. If you kept the default paths, just refresh the site. To use different files,
upload the portrait, the depth map and the closed-eyes image in **Dashboard → Profile & Photo → Living portrait**, then click
the left and right eye on the preview and save.

The first run downloads Depth Anything V2 Small (~100 MB) next to this script (git-ignored).

The closed-eyes image drives the blink: the site reveals it from the top down like a real eyelid.
Check it before uploading (open it and zoom on the eyes). Without it the blink is drawn procedurally.
