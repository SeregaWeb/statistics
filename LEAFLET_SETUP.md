# Leaflet Local Files Setup

Для уменьшения количества HTTP запросов, файлы Leaflet и GeoJSON нужно скачать локально в папку `src` (не в `assets/public`, так как это скомпилированные файлы).

## Инструкция по установке:

### 1. Создайте необходимые директории:

```bash
cd wp-content/themes/wp-rock
mkdir -p src/js/libs/leaflet
mkdir -p src/css/libs/leaflet
mkdir -p src/data
```

### 2. Скачайте файлы Leaflet:

```bash
cd src/js/libs/leaflet
curl -L -o leaflet.js https://unpkg.com/leaflet@1.9.4/dist/leaflet.js
curl -L -o leaflet.markercluster.js https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js

cd ../../css/libs/leaflet
curl -L -o leaflet.css https://unpkg.com/leaflet@1.9.4/dist/leaflet.css
curl -L -o MarkerCluster.css https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css
curl -L -o MarkerCluster.Default.css https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css
```

### 3. Скачайте GeoJSON файл штатов США:

```bash
cd ../../data
curl -L -o us-states.json https://raw.githubusercontent.com/PublicaMundi/MappingAPI/master/data/geojson/us-states.json
```

## Альтернативный способ (через браузер):

Если curl не работает, вы можете скачать файлы вручную:

1. **Leaflet JS**: https://unpkg.com/leaflet@1.9.4/dist/leaflet.js
   - Сохраните как: `src/js/libs/leaflet/leaflet.js`

2. **Leaflet CSS**: https://unpkg.com/leaflet@1.9.4/dist/leaflet.css
   - Сохраните как: `src/css/libs/leaflet/leaflet.css`

3. **MarkerCluster JS**: https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js
   - Сохраните как: `src/js/libs/leaflet/leaflet.markercluster.js`

4. **MarkerCluster CSS**: 
   - https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css → `src/css/libs/leaflet/MarkerCluster.css`
   - https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css → `src/css/libs/leaflet/MarkerCluster.Default.css`

5. **US States GeoJSON**: https://raw.githubusercontent.com/PublicaMundi/MappingAPI/master/data/geojson/us-states.json
   - Сохраните как: `src/data/us-states.json`

После скачивания всех файлов, карта будет работать полностью локально без внешних запросов (кроме тайлов карты, которые загружаются динамически по требованию).

**Важно**: Файлы размещаются в папке `src`, а не в `assets/public`, так как `assets/public` содержит скомпилированные файлы, которые могут быть удалены при сборке проекта.

