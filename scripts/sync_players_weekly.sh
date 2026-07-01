#!/usr/bin/env bash
set -euo pipefail

SCRAPER_REPO="/home/lucas/Documentos/Projects/efootballDBWebScraping"
APP_REPO="/home/lucas/Documentos/MasterLigaOnline"
CSV_PATH="$SCRAPER_REPO/jogadores_tratados.csv"

cd "$SCRAPER_REPO"
source venv/bin/activate

python3 scrapping.py
python3 tratamento.py

deactivate

cd "$APP_REPO"
/home/lucas/.config/herd-lite/bin/php artisan players:import "$CSV_PATH"