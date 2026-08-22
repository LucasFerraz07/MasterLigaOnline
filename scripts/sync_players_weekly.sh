#!/usr/bin/env bash
set -euo pipefail

SCRAPER_REPO="/home/lucas/Documentos/Projects/efootballDBWebScraping"
CSV_PATH="$SCRAPER_REPO/jogadores_tratados.csv"

: "${PLAYER_IMPORT_URL:?Defina PLAYER_IMPORT_URL com a URL do endpoint de importação}"
: "${PLAYER_IMPORT_TOKEN:?Defina PLAYER_IMPORT_TOKEN com o token de importação}"

cd "$SCRAPER_REPO"
source venv/bin/activate

python3 scrapping.py
python3 tratamento.py

deactivate

curl --fail --silent --show-error \
    --request POST \
    --header "X-Player-Import-Token: $PLAYER_IMPORT_TOKEN" \
    --form "file=@${CSV_PATH};type=text/csv" \
    "$PLAYER_IMPORT_URL"
