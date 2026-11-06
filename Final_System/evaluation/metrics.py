"""
Metric helpers (numpy only, no scikit-learn).
"""
import numpy as np


def confusion_matrix(y_true, y_pred, rows: list, cols: list | None = None) -> np.ndarray:
    cols = rows if cols is None else cols
    matrix = np.zeros((len(rows), len(cols)), dtype=int)
    row_index = {label: i for i, label in enumerate(rows)}
    col_index = {label: i for i, label in enumerate(cols)}
    for t, p in zip(y_true, y_pred):
        if t in row_index and p in col_index:
            matrix[row_index[t], col_index[p]] += 1
    return matrix


def prf(tp: int, fp: int, fn: int) -> dict:
    precision = tp / (tp + fp) if tp + fp else 0.0
    recall = tp / (tp + fn) if tp + fn else 0.0
    f1 = 2 * precision * recall / (precision + recall) if precision + recall else 0.0
    return {"precision": precision, "recall": recall, "f1": f1, "tp": tp, "fp": fp, "fn": fn}


def binary_report(y_true, y_pred) -> dict:
    """y_true / y_pred are booleans where True = the positive class (e.g. 'violation')."""
    y_true = np.asarray(y_true, dtype=bool)
    y_pred = np.asarray(y_pred, dtype=bool)
    tp = int((y_true & y_pred).sum())
    fp = int((~y_true & y_pred).sum())
    fn = int((y_true & ~y_pred).sum())
    tn = int((~y_true & ~y_pred).sum())
    report = prf(tp, fp, fn)
    report["tn"] = tn
    report["support"] = int(y_true.sum())
    report["accuracy"] = (tp + tn) / len(y_true) if len(y_true) else 0.0
    report["specificity"] = tn / (tn + fp) if tn + fp else 0.0
    return report


def classification_report(y_true, y_pred, labels: list) -> dict:
    """Per-class P/R/F1 plus accuracy and macro averages."""
    y_true, y_pred = list(y_true), list(y_pred)
    per_class = {}
    for label in labels:
        per_class[label] = binary_report([t == label for t in y_true], [p == label for p in y_pred])
    correct = sum(t == p for t, p in zip(y_true, y_pred))
    return {
        "accuracy": correct / len(y_true) if y_true else 0.0,
        "macro_precision": float(np.mean([per_class[l]["precision"] for l in labels])),
        "macro_recall": float(np.mean([per_class[l]["recall"] for l in labels])),
        "macro_f1": float(np.mean([per_class[l]["f1"] for l in labels])),
        "per_class": per_class,
        "n": len(y_true),
    }


def roc_curve(y_true, scores):
    """Returns (fpr, tpr, thresholds) for a positive-class score, highest threshold first."""
    y_true = np.asarray(y_true, dtype=bool)
    scores = np.asarray(scores, dtype=float)
    order = np.argsort(-scores, kind="mergesort")
    y_sorted, s_sorted = y_true[order], scores[order]
    distinct = np.r_[np.where(np.diff(s_sorted))[0], len(s_sorted) - 1]
    tps = np.cumsum(y_sorted)[distinct]
    fps = (1 + distinct) - tps
    positives, negatives = max(1, y_true.sum()), max(1, (~y_true).sum())
    fpr = np.r_[0.0, fps / negatives]
    tpr = np.r_[0.0, tps / positives]
    return fpr, tpr, np.r_[np.inf, s_sorted[distinct]]


def roc_auc(y_true, scores) -> float | None:
    y_true = np.asarray(y_true, dtype=bool)
    if y_true.all() or (~y_true).all():
        return None
    fpr, tpr, _ = roc_curve(y_true, scores)
    return float(np.trapezoid(tpr, fpr) if hasattr(np, "trapezoid") else np.trapz(tpr, fpr))


def pr_curve(y_true, scores):
    """Returns (recall, precision) points, highest threshold first."""
    y_true = np.asarray(y_true, dtype=bool)
    scores = np.asarray(scores, dtype=float)
    order = np.argsort(-scores, kind="mergesort")
    y_sorted, s_sorted = y_true[order], scores[order]
    distinct = np.r_[np.where(np.diff(s_sorted))[0], len(s_sorted) - 1]
    tps = np.cumsum(y_sorted)[distinct]
    fps = (1 + distinct) - tps
    precision = tps / np.maximum(1, tps + fps)
    recall = tps / max(1, y_true.sum())
    return np.r_[0.0, recall], np.r_[1.0, precision]


def average_precision(y_true, scores) -> float | None:
    y_true = np.asarray(y_true, dtype=bool)
    if not y_true.any():
        return None
    recall, precision = pr_curve(y_true, scores)
    return float(np.sum(np.diff(recall) * precision[1:]))


def summary_stats(values) -> dict:
    values = np.asarray([v for v in values if v is not None], dtype=float)
    if values.size == 0:
        return {"n": 0, "mean": None, "p50": None, "p95": None, "min": None, "max": None}
    return {
        "n": int(values.size),
        "mean": float(values.mean()),
        "p50": float(np.percentile(values, 50)),
        "p95": float(np.percentile(values, 95)),
        "min": float(values.min()),
        "max": float(values.max()),
    }
