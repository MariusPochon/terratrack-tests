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
    navigator.geolocation.getCurrentPosition(
        function (position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            map.setView([lat, lng], 14);
            L.marker([lat, lng]).addTo(map).bindPopup("Vous êtes ici !").openPopup();
        },
        function () {
            console.log("Géolocalisation refusée ou indisponible");
        }
    );
}

geolocalisation();

// --- MARQUEURS COLORÉS ---
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
        iconAnchor: [16, 32]
    });
}

// --- COUCHES ---
// drawnItems  = nouveaux relevés dessinés dans cette session
// savedItems  = relevés chargés depuis la DB au démarrage
const drawnItems = new L.FeatureGroup();
const savedItems = new L.FeatureGroup();
map.addLayer(drawnItems);
map.addLayer(savedItems);

const drawControl = new L.Control.Draw({
    draw: {
        polygon: true,
        marker: {icon: createMarker('#95a5a6')}, // gris = couleur provisoire avant choix catégorie
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

// --- CATÉGORIES ---
// categoriesMap stocke { pk_categorie: { nom, couleur } } pour un accès rapide
const categoriesMap = {};

fetch('categories.php')
    .then(r => r.json())
    .then(categories => {
        const select = document.getElementById('fk_categorie');
        categories.forEach(c => {
            // Stockage pour la mise à jour de couleur
            categoriesMap[c.pk_categorie] = { nom: c.nom, couleur: c.couleur };

            const option = document.createElement('option');
            option.value = c.pk_categorie;
            option.textContent = c.nom;
            // On affiche un petit carré coloré dans le texte de l'option
            option.dataset.couleur = c.couleur;
            select.appendChild(option);
        });
    })
    .catch(err => console.error("Erreur chargement catégories :", err));

// Quand l'utilisateur change de catégorie dans le modal :
// → on met à jour la couleur du marqueur en attente sur la carte
document.getElementById('fk_categorie').addEventListener('change', function () {
    const cat = categoriesMap[this.value];
    if (!cat || !layerEnCours) return;

    // setIcon() ne fonctionne que sur les marqueurs (pas les polygones)
    if (layerEnCours.setIcon) {
        layerEnCours.setIcon(createMarker(cat.couleur));
    } else {
        layerEnCours.setStyle({ color: cat.couleur, fillColor: cat.couleur });
    }
});

// --- POPUP STYLISÉE ---
// Génère le HTML d'une popup avec toutes les infos du relevé
function popupHTML(nom, description, catNom, catCouleur, date, heure, photoIds) {
    const imagesHTML = (photoIds && photoIds.length > 0)
        ? photoIds.map(id =>
            `<img src="photo.php?id=${id}"
                  style="width:100%; margin-top:8px; border-radius:6px; display:block;">`
          ).join('')
        : '';

    const descHTML = description
        ? `<p style="margin:8px 0 0; font-size:13px; color:#444; line-height:1.4;">${description}</p>`
        : '';

    const dateHTML = (date || heure)
        ? `<div style="margin-top:8px; font-size:11px; color:#999;">
               📅 ${date || ''} ${heure ? 'à ' + heure : ''}
           </div>`
        : '';

    return `
        <div style="font-family: sans-serif; max-width: 230px;">
            <div style="
                background: ${catCouleur};
                color: white;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: bold;
                display: inline-block;
                margin-bottom: 6px;
            ">${catNom}</div>
            <div style="font-size: 15px; font-weight: bold; color: #222;">${nom}</div>
            ${descHTML}
            ${dateHTML}
            ${imagesHTML}
        </div>
    `;
}

// --- CHARGEMENT DES RELEVÉS EXISTANTS ---
function chargerReleves() {
    fetch('releves.php')
        .then(r => r.json())
        .then(releves => {
            console.log(`${releves.length} relevé(s) chargé(s) depuis la DB`);
            releves.forEach(afficherReleve);
        })
        .catch(err => console.error("Erreur chargement relevés :", err));
}

// Crée et affiche un relevé (marqueur ou polygone) sur la carte
function afficherReleve(r) {
    const couleur = r.cat_couleur || '#3498db';
    let layer;

    if (r.type === 'point') {
        layer = L.marker([r.latitude, r.longitude], {
            icon: createMarker(couleur)
        });

    } else if (r.type === 'polygone' && r.points.length > 0) {
        const latlngs = r.points.map(p => [p.latitude, p.longitude]);
        layer = L.polygon(latlngs, {
            color: couleur,
            fillColor: couleur,
            fillOpacity: 0.3
        });
    } else {
        return;
    }

    layer.bindPopup(
        popupHTML(r.nom, r.description, r.cat_nom, couleur,
                  r.date_enregistrement, r.heure_enregistrement, r.photos),
        { maxWidth: 260 }
    );

    savedItems.addLayer(layer);
}

chargerReleves();

// --- MODAL : références aux éléments HTML ---
const overlay  = document.getElementById('modal-overlay');
const form     = document.getElementById('form-releve');
const statusEl = document.getElementById('modal-status');

let layerEnCours = null;

function ouvrirModal(type, lat, lng, points) {
    form.reset();
    statusEl.textContent = '';

    const now = new Date();
    form.date_enregistrement.value = now.toISOString().slice(0, 10);
    form.heure_enregistrement.value = now.toTimeString().slice(0, 8);

    form.type.value = type;

    if (type === 'point') {
        form.latitude.value  = lat;
        form.longitude.value = lng;
    } else {
        form.points_json.value = JSON.stringify(points);
    }

    overlay.classList.add('actif');
}

function fermerModal(annuler = false) {
    if (annuler && layerEnCours) {
        drawnItems.removeLayer(layerEnCours);
    }
    layerEnCours = null;
    overlay.classList.remove('actif');
}

document.getElementById('btn-annuler').addEventListener('click', function () {
    fermerModal(true);
});

overlay.addEventListener('click', function (e) {
    if (e.target === overlay) fermerModal(true);
});

// --- SOUMISSION DU FORMULAIRE ---
form.addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(form);

    if (form.type.value === 'polygone') {
        const points = JSON.parse(form.points_json.value);
        points.forEach((p, i) => {
            formData.append(`points[${i}][latitude]`,  p.lat);
            formData.append(`points[${i}][longitude]`, p.lng);
        });
        formData.delete('points_json');
    }

    statusEl.textContent = "Envoi en cours...";

    fetch('save.php', {method: 'POST', body: formData})
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                statusEl.textContent = "Enregistré ✅ (id " + data.id + ")";

                // Récupère les infos de catégorie choisie pour la popup
                const pkCat  = form.fk_categorie.value;
                const cat    = categoriesMap[pkCat] || { nom: '', couleur: '#3498db' };
                const nom    = formData.get('nom');
                const desc   = formData.get('description') || '';
                const date   = form.date_enregistrement.value;
                const heure  = form.heure_enregistrement.value;

                layerEnCours.bindPopup(
                    popupHTML(nom, desc, cat.nom, cat.couleur, date, heure, data.photos),
                    { maxWidth: 260 }
                );

                setTimeout(() => fermerModal(false), 1000);
            } else {
                statusEl.textContent = "Erreur : " + (data.error || 'inconnue');
            }
        })
        .catch(err => {
            statusEl.textContent = "Erreur réseau";
            console.error(err);
        });
});

// --- ÉVÉNEMENT DÉCLENCHÉ QUAND ON TERMINE DE DESSINER ---
map.on(L.Draw.Event.CREATED, function (e) {
    const layer = e.layer;
    const type  = e.layerType;

    // Couleur provisoire (gris) — sera mise à jour quand l'utilisateur choisit la catégorie
    if (type === 'polygon') {
        layer.setStyle({ color: '#95a5a6', fillColor: '#95a5a6', fillOpacity: 0.3 });
    }

    drawnItems.addLayer(layer);
    layerEnCours = layer;

    if (type === 'marker') {
        const coords = layer.getLatLng();
        ouvrirModal('point', coords.lat, coords.lng, null);
    } else if (type === 'polygon') {
        const points = layer.getLatLngs()[0];
        ouvrirModal('polygone', null, null, points);
    }
});
