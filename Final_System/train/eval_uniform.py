"""
Evaluate uniform classifiers on an ImageFolder split (Non_Uniform/, Uniform/) using the
same preprocessing as the running app (Utils.preprocess).

Usage (from Final_System):
    python -m train.eval_uniform <split_dir> [model.pt ...]
    python -m train.eval_uniform debug_crops_sorted models/kd_student_seed42_best_ver1.pt
"""
import glob
import os
import sys

import cv2
import numpy as np
import torch

import ai.config as my_config
from ai.classifier import Classifier
from ai.my_utils import Utils

CLASSES = ["Non_Uniform", "Uniform"]


def load_items(split_dir):
    items = []
    for class_index, class_name in enumerate(CLASSES):
        for path in sorted(glob.glob(os.path.join(split_dir, class_name, "*"))):
            items.append((path, class_index))
    return items


def predict(classifier, items, batch_size=64):
    probabilities = []
    for start in range(0, len(items), batch_size):
        tensors = []
        for path, _ in items[start:start + batch_size]:
            image = cv2.imdecode(np.fromfile(path, np.uint8), cv2.IMREAD_COLOR)
            tensors.append(Utils.preprocess(image, my_config.CLASSIFY_IMAGE_SIZE))
        with torch.no_grad():
            logits = classifier.model(torch.cat(tensors).to(classifier.device))
            probabilities.append(torch.softmax(logits, dim=1).cpu())
    return torch.cat(probabilities).numpy()


def main():
    if len(sys.argv) < 2:
        print(__doc__)
        sys.exit(1)
    split_dir = sys.argv[1]
    model_paths = sys.argv[2:] or [str(my_config.CLASSIFY_UNIFORM_PATH)]
    items = load_items(split_dir)
    if not items:
        sys.exit(f"No images found under {split_dir}/{{{','.join(CLASSES)}}}")
    targets = np.array([label for _, label in items])

    for model_path in model_paths:
        classifier = Classifier(model_path, num_class=len(CLASSES), labels=CLASSES)
        probabilities = predict(classifier, items)
        preds = probabilities.argmax(1)
        matrix = np.zeros((2, 2), dtype=int)
        for target, pred in zip(targets, preds):
            matrix[target, pred] += 1
        recalls = [matrix[i, i] / max(1, matrix[i].sum()) for i in range(2)]
        print(f"{os.path.basename(model_path)}: acc={(preds == targets).mean():.3f} "
              f"recall_NU={recalls[0]:.3f} recall_U={recalls[1]:.3f} "
              f"confusion(rows=true NU,U)={matrix.tolist()}")


if __name__ == "__main__":
    main()
