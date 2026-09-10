import sys
from PIL import Image

SRC = "assets/images/logo_asikkekku.png"
DST = "assets/images/logo_baru.png"

im = Image.open(SRC).convert("RGBA")
px = im.load()
w, h = im.size

def near_white(r, g, b, thr=232):
    return r >= thr and g >= thr and b >= thr

# 1) global pass: near-white -> transparent
for y in range(h):
    for x in range(w):
        r, g, b, a = px[x, y]
        if near_white(r, g, b):
            px[x, y] = (r, g, b, 0)

# 2) flood fill from the 4 corners to clear any off-white halo connected to the edge
from collections import deque
visited = set()
dq = deque()
for cx, cy in ((0,0),(w-1,0),(0,h-1),(w-1,h-1)):
    dq.append((cx, cy))
while dq:
    x, y = dq.popleft()
    if (x, y) in visited or x < 0 or y < 0 or x >= w or y >= h:
        continue
    visited.add((x, y))
    r, g, b, a = px[x, y]
    if a == 0 or (r >= 200 and g >= 200 and b >= 200):
        px[x, y] = (r, g, b, 0)
        dq.extend([(x+1,y),(x-1,y),(x,y+1),(x,y-1)])

im.save(DST)
print("wrote", DST, im.size)
