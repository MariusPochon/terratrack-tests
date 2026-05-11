// On initialise la carte dans la div "map"
// Le tableau [46.8, 7.15] = coordonnées de Fribourg (latitude, longitude)
// Le 13 = niveau de zoom de départ
var map = L.map('map').setView([46.8, 7.15], 15);

// On ajoute les tuiles OpenStreetMap (les images de la carte)
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

// On demande la position au navigateur
navigator.geolocation.getCurrentPosition(
    // Cas 1 : l'utilisateur accepte
    function (position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;

        // On recentre la carte sur la position de l'utilisateur
        map.setView([lat, lng], 15);

        // On ajoute un marqueur à sa position
        L.marker([lat, lng])
            .addTo(map)
            .bindPopup("Vous êtes ici !")
            .openPopup();
    },

    // Cas 2 : l'utilisateur refuse ou erreur
    function (erreur) {
        console.log("Géolocalisation refusée ou indisponible");
        // La carte reste centrée sur Fribourg par défaut
    }
);

// Fonction qui crée une icône de la couleur qu'on veut
function creerIcone(couleur) {
    return L.icon({
        iconUrl: `img/marker-icon-${couleur}.png`,
        shadowUrl: 'img/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
}

// Test avec différentes couleurs selon la catégorie
L.marker([46.80, 7.15], {icon: creerIcone('gold')})
    .addTo(map)
    .bindPopup("Faune sauvage");

L.marker([46.81, 7.16], {icon: creerIcone('black')})
    .addTo(map)
    .bindPopup("Flore protégée");

L.marker([46.79, 7.14], {icon: creerIcone('red')})
    .addTo(map)
    .bindPopup("Patrimoine historique");

// Couche qui va stocker les zones dessinées
const drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);

// Barre d'outils de dessin
const drawControl = new L.Control.Draw({
    draw: {
        polygon: true,   // zones
        marker: false,   // on gère nos propres marqueurs
        circle: false,   // pas besoin
        rectangle: false,
        polyline: false,
        circlemarker: false
    },
    edit: {
        featureGroup: drawnItems
    }
});
map.addControl(drawControl);

zone.bindPopup(`
    <h3>Nom de la zone</h3>
    <p>Description ici</p>
    <img src="photo.jpg" width="100%"/>
`).openPopup();

// Quand l'utilisateur termine de dessiner
map.on(L.Draw.Event.CREATED, function(e) {
    const zone = e.layer;

    zone.setStyle({
        color: '#2ecc71',        // bordure
        fillColor: '#2ecc71',    // remplissage
        fillOpacity: 0.3         // transparence
    });
    drawnItems.addLayer(zone);

    // Récupérer les coordonnées
    const coordonnees = zone.getLatLngs()[0];
    console.log(coordonnees); // tableau de points
});