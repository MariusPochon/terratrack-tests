// On initialise la carte dans la div "map"
// [46.8, 7.15] = coordonnées de Fribourg | 15 = niveau de zoom
const map = L.map('map').setView([46.8, 7.15], 15);

// On ajoute les tuiles OpenStreetMap
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

// --- GÉOLOCALISATION ---
function geolocalisation() {
    console.log("Tentative de géolocalisation...");
    navigator.geolocation.getCurrentPosition(
        function (position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            map.setView([lat, lng], 14);
            L.marker([lat, lng])
                .addTo(map)
                .bindPopup("Vous êtes ici !")
                .openPopup();
        },
        function () {
            console.log("Géolocalisation refusée ou indisponible");
        }
    );
}
geolocalisation();

// --- MARQUEURS COLORÉS ---
// L.divIcon permet de créer une icône à partir de HTML pur
// currentColor hérite la couleur CSS du div parent → 1 seul endroit à changer
function createMarker(color) {
    return L.divIcon({
        className: '',
        html: `
            <div style="color: ${color}">
                <svg viewBox="0 0 24 24" width="32" height="32">
                    <path fill="currentColor"
                        d="M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7z"/>
                </svg>
            </div>
        `,
        iconSize: [32, 32],
        iconAnchor: [16, 32]  // centre bas = pointe du pin sur les coordonnées exactes
    });
}

// Marqueurs de test avec différentes catégories
L.marker([46.80, 7.15], { icon: createMarker('#e74c3c') })
    .addTo(map).bindPopup("Faune sauvage");

L.marker([46.81, 7.16], { icon: createMarker('#2ecc71') })
    .addTo(map).bindPopup("Flore protégée");

L.marker([46.79, 7.14], { icon: createMarker('#f39c12') })
    .addTo(map).bindPopup("Patrimoine historique");

// --- DESSIN DE ZONES ET MARKERS ---
// FeatureGroup = couche qui stocke tout ce qu'on dessine
const drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);

// On configure les outils de dessin disponibles
const drawControl = new L.Control.Draw({
    draw: {
        polygon: true,
        // On passe notre icône custom à Leaflet.draw
        marker: { icon: createMarker('#e74c3c') },
        circle: false,
        rectangle: false,
        polyline: false,
        circlemarker: false
    },
    edit: {
        featureGroup: drawnItems
    }
});
map.addControl(drawControl);

// Événement déclenché quand l'utilisateur termine de dessiner
map.on(L.Draw.Event.CREATED, function(e) {
    const layer = e.layer;
    const type = e.layerType; // "marker" ou "polygon"

    if (type === 'marker') {
        // Les marqueurs n'ont pas de setStyle() → on gère séparément
        const coords = layer.getLatLng();
        layer.bindPopup(`Marqueur posé à : ${coords.lat.toFixed(5)}, ${coords.lng.toFixed(5)}`).openPopup();
        console.log("Coordonnées du marqueur :", coords);

    } else if (type === 'polygon') {
        // Les polygones acceptent setStyle()
        layer.setStyle({
            color: '#2ecc71',
            fillColor: '#2ecc71',
            fillOpacity: 0.3
        });
        const coordonnees = layer.getLatLngs()[0];
        layer.bindPopup(`Zone avec ${coordonnees.length} points`).openPopup();
        console.log("Coordonnées de la zone :", coordonnees);
    }

    drawnItems.addLayer(layer);
});