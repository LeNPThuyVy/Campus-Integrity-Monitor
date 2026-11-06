"""
Static PNG charts for the report (light theme, print-friendly).
"""
from pathlib import Path

import matplotlib

matplotlib.use("Agg")
import matplotlib.pyplot as plt  # noqa: E402
from matplotlib.colors import LinearSegmentedColormap  # noqa: E402
import numpy as np  # noqa: E402

SURFACE = "#fcfcfb"
INK = "#0b0b0b"
INK_SECONDARY = "#52514e"
GRID = "#e4e3df"
# Categorical slots, always assigned in this order.
SERIES = ["#2a78d6", "#eb6834", "#1baf7a", "#eda100", "#e87ba4", "#008300", "#6250d6", "#e34948"]
SEQUENTIAL = LinearSegmentedColormap.from_list("blue_seq", ["#f0efec", "#9ec5f4", "#3987e5", "#1c5cab", "#0d366b"])

plt.rcParams.update({
    "figure.facecolor": SURFACE,
    "axes.facecolor": SURFACE,
    "axes.edgecolor": GRID,
    "axes.labelcolor": INK_SECONDARY,
    "axes.titlecolor": INK,
    "axes.titlesize": 12,
    "axes.titleweight": "bold",
    "axes.spines.top": False,
    "axes.spines.right": False,
    "axes.grid": True,
    "grid.color": GRID,
    "grid.linewidth": 0.8,
    "xtick.color": INK_SECONDARY,
    "ytick.color": INK_SECONDARY,
    "legend.frameon": False,
    "font.size": 10,
    "lines.linewidth": 2,
    "savefig.dpi": 160,
    "savefig.bbox": "tight",
})


def _save(fig, path) -> str:
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    fig.savefig(path)
    plt.close(fig)
    return str(path)


def confusion(matrix, rows, cols, title, path, xlabel="Dự đoán", ylabel="Thực tế"):
    matrix = np.asarray(matrix)
    fig, ax = plt.subplots(figsize=(1.3 * len(cols) + 2, 1.0 * len(rows) + 1.6))
    ax.grid(False)
    ax.imshow(matrix, cmap=SEQUENTIAL, vmin=0, vmax=max(1, matrix.max()))
    for i in range(matrix.shape[0]):
        for j in range(matrix.shape[1]):
            dark = matrix[i, j] > matrix.max() * 0.55
            ax.text(j, i, str(matrix[i, j]), ha="center", va="center", fontsize=11,
                    color="#ffffff" if dark else INK)
    ax.set_xticks(range(len(cols)), cols)
    ax.set_yticks(range(len(rows)), rows)
    ax.set_xlabel(xlabel)
    ax.set_ylabel(ylabel)
    ax.set_title(title, loc="left")
    return _save(fig, path)


def curves(series: list[tuple[str, np.ndarray, np.ndarray]], title, xlabel, ylabel, path, diagonal=False):
    """series: [(name, x, y), ...] drawn as lines in fixed color order."""
    fig, ax = plt.subplots(figsize=(5.5, 4.5))
    if diagonal:
        ax.plot([0, 1], [0, 1], color=GRID, linewidth=1, linestyle="--")
    for index, (name, x, y) in enumerate(series):
        ax.plot(x, y, color=SERIES[index % len(SERIES)], label=name)
    ax.set_xlim(0, 1)
    ax.set_ylim(0, 1.02)
    ax.set_xlabel(xlabel)
    ax.set_ylabel(ylabel)
    ax.set_title(title, loc="left")
    if len(series) > 1:
        ax.legend(loc="lower right")
    return _save(fig, path)


def sweep(x, lines: dict[str, list], title, xlabel, path, current=None, ylabel="Giá trị", ylim=(0, 1.02)):
    """One parameter on x, one or more metrics (same scale) as lines; marks the current setting."""
    fig, ax = plt.subplots(figsize=(6, 4))
    for index, (name, y) in enumerate(lines.items()):
        ax.plot(x, y, color=SERIES[index], marker="o", markersize=5, label=name)
    if current is not None:
        ax.axvline(current, color=INK_SECONDARY, linewidth=1, linestyle=":")
        ax.annotate("cấu hình hiện tại", (current, ylim[1] if ylim else 1), fontsize=8,
                    color=INK_SECONDARY, ha="left", va="top", xytext=(4, -2), textcoords="offset points")
    if ylim:
        ax.set_ylim(*ylim)
    ax.set_xlabel(xlabel)
    ax.set_ylabel(ylabel)
    ax.set_title(title, loc="left")
    if len(lines) > 1:
        ax.legend(loc="lower left")
    return _save(fig, path)


def stacked_bars(categories: list[str], stacks: dict[str, list[float]], title, ylabel, path):
    fig, ax = plt.subplots(figsize=(max(5, 1.2 * len(categories) + 2), 4.2))
    bottom = np.zeros(len(categories))
    for index, (name, values) in enumerate(stacks.items()):
        values = np.asarray(values, dtype=float)
        ax.bar(categories, values, bottom=bottom, color=SERIES[index % len(SERIES)], label=name,
               width=0.6, edgecolor=SURFACE, linewidth=2)
        bottom += values
    for i, total in enumerate(bottom):
        ax.text(i, total, f"{total:.0f} ms", ha="center", va="bottom", fontsize=9, color=INK)
    ax.set_ylabel(ylabel)
    ax.set_title(title, loc="left")
    ax.grid(axis="x", visible=False)
    ax.legend(loc="upper left", bbox_to_anchor=(1, 1))
    plt.setp(ax.get_xticklabels(), rotation=20, ha="right")
    return _save(fig, path)


def line(x, y, title, xlabel, ylabel, path, band=None):
    """Single series; band = (low, high) arrays drawn as a light range (e.g. p5-p95)."""
    fig, ax = plt.subplots(figsize=(6, 4))
    if band is not None:
        ax.fill_between(x, band[0], band[1], color=SERIES[0], alpha=0.15, linewidth=0)
    ax.plot(x, y, color=SERIES[0], marker="o", markersize=5)
    ax.set_xlabel(xlabel)
    ax.set_ylabel(ylabel)
    ax.set_title(title, loc="left")
    return _save(fig, path)


def histogram(values, title, xlabel, path, bins=40):
    fig, ax = plt.subplots(figsize=(6, 4))
    ax.hist(values, bins=bins, color=SERIES[0], edgecolor=SURFACE, linewidth=1)
    ax.set_xlabel(xlabel)
    ax.set_ylabel("Số frame")
    ax.set_title(title, loc="left")
    return _save(fig, path)
