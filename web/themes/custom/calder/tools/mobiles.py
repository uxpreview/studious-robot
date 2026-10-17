#!/usr/bin/env python3
"""
Generates Calder's illustration system: hanging mobiles that actually balance.

Each mobile is a tree. A leaf is a flat, slightly irregular shape (Calder's
"petals"). A branch is a wire arm with two children hanging from its ends; the
pivot sits where the torque balances (weight_left * arm_left == weight_right *
arm_right), so every composition is physically plausible.

Run from the theme directory:  python3 tools/mobiles.py
Writes SVGs to images/mobiles/. Output is deterministic (seeded).
"""

import math
import os
import random

INK = "#16181d"
CARE = "#c23a2b"
LEARN = "#2346b0"
DISCOVER = "#ebaa2a"
PAPER = "#faf7f2"
SAND = "#f2ede4"

OUT = os.path.join(os.path.dirname(__file__), "..", "images", "mobiles")


class Leaf:
    def __init__(self, size, color, kind="petal", rot=0.0):
        self.size, self.color, self.kind, self.rot = size, color, kind, rot

    @property
    def weight(self):
        return self.size ** 2


class Arm:
    def __init__(self, left, right, drop_left=40, drop_right=40, length=None, sag=0.16):
        self.left, self.right = left, right
        self.drop_left, self.drop_right = drop_left, drop_right
        self.length, self.sag = length, sag

    @property
    def weight(self):
        return self.left.weight + self.right.weight


def petal_path(cx, cy, r, rot, rng, kind):
    """An irregular rounded shape, built from a closed Catmull-Rom loop."""
    if kind == "disc":
        return f'<circle cx="{cx:.1f}" cy="{cy:.1f}" r="{r:.1f}"/>'
    n = 7 if kind == "petal" else 9
    pts = []
    for i in range(n):
        a = rot + i * 2 * math.pi / n
        if kind == "petal":
            # Leaf-like: long axis and a slight asymmetry.
            rr = r * (1.0 + 0.32 * math.cos(a - rot)) * rng.uniform(0.9, 1.06)
            rr *= 0.78 if math.sin(a - rot) > 0 else 1.0
        else:  # blob
            rr = r * rng.uniform(0.84, 1.1)
        pts.append((cx + rr * math.cos(a), cy + rr * math.sin(a)))
    d = []
    for i in range(n):
        p0, p1, p2, p3 = pts[i - 1], pts[i], pts[(i + 1) % n], pts[(i + 2) % n]
        c1 = (p1[0] + (p2[0] - p0[0]) / 6, p1[1] + (p2[1] - p0[1]) / 6)
        c2 = (p2[0] - (p3[0] - p1[0]) / 6, p2[1] - (p3[1] - p1[1]) / 6)
        if i == 0:
            d.append(f"M{p1[0]:.1f},{p1[1]:.1f}")
        d.append(f"C{c1[0]:.1f},{c1[1]:.1f} {c2[0]:.1f},{c2[1]:.1f} {p2[0]:.1f},{p2[1]:.1f}")
    return f'<path d="{" ".join(d)}Z"/>'


def extents(node):
    """Horizontal reach (left, right) of a subtree from its hanging point."""
    if isinstance(node, Leaf):
        return node.size * 1.3, node.size * 1.3
    left_len, right_len = arm_lengths(node)
    l1, r1 = extents(node.left)
    l2, r2 = extents(node.right)
    return max(left_len + l1, 0), max(right_len + r2, 0)


def arm_lengths(node):
    """Arm lengths that balance the torque and keep the two sides apart."""
    _, r1 = extents(node.left)
    l2, _ = extents(node.right)
    base = node.length or 70 + node.weight ** 0.5 * 1.25
    total = max(base, r1 + l2 + 24)
    # Torque balance: the heavier side gets the shorter arm.
    left_len = total * node.right.weight / node.weight
    return left_len, total - left_len


def render(node, x, y, rng, out, depth=0):
    """Hangs `node` from the point (x, y). Returns the bounding box."""
    if isinstance(node, Leaf):
        cy = y + node.size
        out["wires"].append(f'<line x1="{x:.1f}" y1="{y:.1f}" x2="{x:.1f}" y2="{cy - node.size * 0.6:.1f}"/>')
        out["shapes"].append((node.color, petal_path(x, cy, node.size, node.rot, rng, node.kind)))
        return (x - node.size * 1.35, y, x + node.size * 1.35, cy + node.size * 1.35)

    left_len, right_len = arm_lengths(node)
    total = left_len + right_len
    lx, rx = x - left_len, x + right_len
    sag = total * node.sag
    # The arm: one sweeping wire that rises at the pivot and droops at the ends.
    ly, ry = y + sag * 0.55, y + sag * 0.55
    out["wires"].append(
        f'<path d="M{lx:.1f},{ly:.1f} C{lx + total * 0.18:.1f},{y - sag * 0.6:.1f} {rx - total * 0.18:.1f},{y - sag * 0.6:.1f} {rx:.1f},{ry:.1f}"/>'
    )
    out["joints"].append(f'<circle cx="{x:.1f}" cy="{y - sag * 0.3:.1f}" r="2.6"/>')
    out["wires"].append(f'<line x1="{lx:.1f}" y1="{ly:.1f}" x2="{lx:.1f}" y2="{ly + node.drop_left:.1f}"/>')
    out["wires"].append(f'<line x1="{rx:.1f}" y1="{ry:.1f}" x2="{rx:.1f}" y2="{ry + node.drop_right:.1f}"/>')
    b1 = render(node.left, lx, ly + node.drop_left, rng, out, depth + 1)
    b2 = render(node.right, rx, ry + node.drop_right, rng, out, depth + 1)
    return (min(b1[0], b2[0], lx), y - sag, max(b1[2], b2[2], rx), max(b1[3], b2[3]))


def svg(name, tree, seed, width=800, height=600, bg=None, title="", stroke=2.24, fill_ratio=(0.82, 0.86), joint=True):
    rng = random.Random(seed)
    out = {"wires": [], "joints": [], "shapes": []}
    # Render once to measure, then center inside the canvas.
    box = render(tree, 0, 0, random.Random(seed), {"wires": [], "joints": [], "shapes": []})
    bw, bh = box[2] - box[0], box[3] - box[1] + 40
    scale = min((width * fill_ratio[0]) / bw, (height * fill_ratio[1]) / bh)
    tx = width / 2 - (box[0] + bw / 2) * scale
    ty = height * 0.06 + 40 * scale
    render(tree, 0, 0, rng, out)
    top_x = 0
    parts = [
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {width} {height}" width="{width}" height="{height}" color="{INK}" role="img" aria-label="{title}">',
        f"<title>{title}</title>",
    ]
    if bg:
        parts.append(f'<rect width="{width}" height="{height}" fill="{bg}"/>')
    # transform-origin marks the hanging point, so CSS can sway the mobile from it.
    # The outer group only carries the pivot; transform-origin would otherwise
    # also apply to the inner group's translate/scale.
    parts.append(f'<g style="transform-origin: {tx:.1f}px 0px"><g transform="translate({tx:.1f} {ty:.1f}) scale({scale:.3f})">')
    # The hanging string from the ceiling.
    parts.append(f'<line x1="{top_x}" y1="{-ty / scale - 2:.1f}" x2="{top_x}" y2="{-tree_sag(tree) * 0.3:.1f}" stroke="currentColor" stroke-width="{stroke / scale:.2f}"/>')
    sw = stroke / scale
    parts.append(f'<g fill="none" stroke="currentColor" stroke-width="{sw:.2f}" stroke-linecap="round">{"".join(out["wires"])}</g>')
    if joint:
        parts.append(f'<g fill="currentColor">{"".join(out["joints"])}</g>')
    for color, shape in out["shapes"]:
        parts.append(f'<g fill="{"currentColor" if color == INK else color}">{shape}</g>')
    parts.append("</g></g></svg>")
    os.makedirs(OUT, exist_ok=True)
    with open(os.path.join(OUT, name + ".svg"), "w") as fh:
        fh.write("\n".join(parts))


def tree_sag(tree):
    if isinstance(tree, Leaf):
        return 0
    left_len, right_len = arm_lengths(tree)
    return (left_len + right_len) * tree.sag


# --- Compositions -----------------------------------------------------------

L = Leaf
A = Arm

COMPOSITIONS = {
    # Home: all three missions, held in balance.
    "home": (
        A(
            A(L(34, CARE, "petal", 0.4), L(20, INK, "disc"), 70, 120),
            A(L(26, LEARN, "petal", 2.2), A(L(16, DISCOVER, "disc"), L(22, DISCOVER, "petal", 1.1), 40, 90), 140, 60),
            60,
            40,
        ),
        11,
        "A balanced hanging mobile with red, blue and ochre shapes",
    ),
    "care": (
        A(L(40, CARE, "petal", 0.2), A(L(18, INK, "disc"), L(24, CARE, "blob"), 60, 110), 90, 50),
        3,
        "A hanging mobile in red, for patient care",
    ),
    "learn": (
        A(A(L(22, LEARN, "petal", 1.4), L(14, INK, "disc"), 50, 90), L(36, LEARN, "blob"), 40, 130),
        5,
        "A hanging mobile in blue, for education",
    ),
    "discover": (
        A(L(30, DISCOVER, "blob"), A(L(20, DISCOVER, "petal", 2.6), A(L(12, INK, "disc"), L(16, LEARN, "disc"), 30, 60), 70, 50), 110, 40),
        7,
        "A hanging mobile in ochre, for research",
    ),
    "heart": (
        A(L(42, CARE, "petal", 4.0), A(L(16, INK, "disc"), L(20, CARE, "disc"), 80, 40), 60, 100),
        13,
        "A hanging mobile with a large red shape",
    ),
    "brain": (
        A(A(L(18, LEARN, "blob"), L(22, LEARN, "petal", 0.9), 90, 50), A(L(14, INK, "disc"), L(28, LEARN, "blob"), 40, 110), 50, 70),
        17,
        "A hanging mobile with clustered blue shapes",
    ),
    "child": (
        A(A(L(16, DISCOVER, "disc"), L(20, CARE, "petal", 1.8), 40, 70), A(L(14, LEARN, "disc"), L(24, DISCOVER, "blob"), 90, 40), 60, 50),
        19,
        "A small, playful hanging mobile in ochre, red and blue",
    ),
    "story-red": (
        A(L(30, CARE, "petal", 5.1), L(18, INK, "disc"), 60, 120),
        23,
        "A simple red and black hanging mobile",
    ),
    "story-blue": (
        A(L(20, INK, "disc"), A(L(26, LEARN, "petal", 3.3), L(18, LEARN, "blob"), 50, 90), 120, 60),
        29,
        "A blue and black hanging mobile",
    ),
    "story-ochre": (
        A(A(L(22, DISCOVER, "blob"), L(12, INK, "disc"), 70, 40), L(28, DISCOVER, "petal", 0.6), 60, 110),
        31,
        "An ochre hanging mobile",
    ),
}

# The logo mark: one shape per mission, in balance.
MARK = A(L(30, CARE, "petal", 0.5), A(L(20, LEARN, "disc"), L(16, DISCOVER, "disc"), 26, 54, sag=0.2), 34, 22, sag=0.2)

if __name__ == "__main__":
    svg("mark", MARK, 2, width=64, height=64, title="Calder University Health", stroke=1.6, fill_ratio=(0.9, 0.9), joint=False)
    for name, (tree, seed, title) in COMPOSITIONS.items():
        svg(name, tree, seed, title=title)
        # Card versions on sand, 4:3, for news and listing images.
        svg(name + "-sand", tree, seed, width=1200, height=900, bg=SAND, title=title)
    print("Wrote", len(COMPOSITIONS) * 2, "SVGs to", os.path.normpath(OUT))
