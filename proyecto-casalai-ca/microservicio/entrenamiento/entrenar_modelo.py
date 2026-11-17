import sys
from pathlib import Path


MICROSERVICIO_DIR = Path(__file__).resolve().parents[1]
if str(MICROSERVICIO_DIR) not in sys.path:
    sys.path.insert(0, str(MICROSERVICIO_DIR))

from retrain import run_retraining


if __name__ == "__main__":
    try:
        run_retraining()
    except (OSError, ValueError) as error:
        print(f"Error de reentrenamiento: {error}", file=sys.stderr)
        sys.exit(1)
