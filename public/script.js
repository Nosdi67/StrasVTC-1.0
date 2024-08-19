document.addEventListener('DOMContentLoaded', function() {

    if (typeof L === 'undefined') {
        console.error('Leaflet is not loaded!');//debbug
        return;
    }
    /******************bouton toggle */
    document.querySelectorAll('.vehicle-btn').forEach(button => {
        button.addEventListener('click', function() {
            // réinitialiser les boutons
            document.querySelectorAll('.vehicle-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // activer le bouton
            this.classList.add('active');
        });
    });

    // Initialisation de la carte centrée sur Paris
    var map = L.map('map').setView([48.8566, 2.3522], 16);

    // Ajout des tuiles OpenStreetMap à la carte
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    // Déclaration des variables pour les marqueurs de départ et de destination
    let departureMarker;
    let destinationMarker;
    let routingControl; // Variable pour garder une référence au contrôle de l'itinéraire

    // Récupération des éléments d'interface pour les entrées de texte et les suggestions
    let departureInput = document.getElementById('departure');
    let destinationInput = document.getElementById('destination');
    let departureSuggestionsList = document.getElementById('departure-suggestions');
    let destinationSuggestionsList = document.getElementById('destination-suggestions');
    let itineraryDiv = document.getElementById('itinerary');

    // Indicateurs pour vérifier si des suggestions valides sont sélectionnées
    let isDepartureValid = false;
    let isDestinationValid = false;

    // Fonction pour afficher le bloc d'info de course
    function validateAddresses() {
        if (isDepartureValid && isDestinationValid) {
            console.log('Les deux adresses sont valides. Affichage de l\'itinéraire.');
            itineraryDiv.style.display = 'block';
        } else {
            console.log('Une ou les deux adresses sont invalides. Masquage de l\'itinéraire.');
            itineraryDiv.style.display = 'none';
        }
    }

    // Fonction pour ajouter des suggestions dans la liste
    function addSuggestions(inputElement, suggestionsElement, data, isDeparture) {
        // Stocker les dernières suggestions
        if (isDeparture) {
            lastDepartureSuggestions = data.slice(0, 5);
        } else {
            lastDestinationSuggestions = data.slice(0, 5);
        }

        // Vider les suggestions précédentes
        suggestionsElement.innerHTML = '';
        // Ajouter les nouvelles suggestions
        data.slice(0, 5).forEach(item => {
            const li = document.createElement('li');
            li.textContent = item.display_name;
            li.addEventListener('click', function() {
                inputElement.value = item.display_name;
                suggestionsElement.innerHTML = ''; // Effacer les suggestions

                if (isDeparture) {
                    const departureAddressElement = document.getElementById('selected-departure-address');
                    if (departureAddressElement) {
                        departureAddressElement.value = item.display_name;
                    }
                    isDepartureValid = true; // Définir l'indicateur sur vrai pour un départ valide
                } else {
                    const destinationAddressElement = document.getElementById('selected-destination-address');
                    if (destinationAddressElement) {
                        destinationAddressElement.value = item.display_name;
                    }
                    isDestinationValid = true; // Définir l'indicateur sur vrai pour une destination valide
                }

                validateAddresses(); // Valider les adresses après sélection

                // Ajouter un marqueur à la carte
                const latLng = [item.lat, item.lon];
                if (isDeparture) {
                    if (departureMarker) map.removeLayer(departureMarker);
                    departureMarker = L.marker(latLng).addTo(map);
                    map.flyTo(latLng, 15); // Centrer la carte sur le nouveau marqueur
                } else {
                    if (destinationMarker) map.removeLayer(destinationMarker);
                    destinationMarker = L.marker(latLng).addTo(map);
                }

                // Calculer l'itinéraire si les deux marqueurs sont définis
                if (departureMarker && destinationMarker) {
                    calculateRoute(departureMarker.getLatLng(), destinationMarker.getLatLng());
                }
            });
            suggestionsElement.appendChild(li);
        });
    }

    // Écouteurs d'événements pour les entrées de texte de départ et de destination
    departureInput.addEventListener('input', function() {
        handleInput(this, departureSuggestionsList, true);
    });

    destinationInput.addEventListener('input', function() {
        handleInput(this, destinationSuggestionsList, false);
    });

    // Fonction pour gérer les entrées de texte et afficher les suggestions
    function handleInput(inputElement, suggestionsElement, isDeparture) {
        const query = inputElement.value;
        if (query.length > 2) {
            fetch(`https://nominatim.openstreetmap.org/search?q=${query}&format=json`)
                .then(response => response.json())
                .then(data => {
                    addSuggestions(inputElement, suggestionsElement, data, isDeparture);
                });
        }
    }

    // Ajouter des écouteurs d'événements pour blur et focus
    departureInput.addEventListener('blur', function() {
        setTimeout(() => departureSuggestionsList.innerHTML = '', 100); // Délai pour permettre l'enregistrement de l'événement 
    });

    departureInput.addEventListener('focus', function() {
        restoreSuggestions(departureSuggestionsList, lastDepartureSuggestions);
    });

    destinationInput.addEventListener('blur', function() {
        setTimeout(() => destinationSuggestionsList.innerHTML = '', 100); // Délai pour permettre l'enregistrement de l'événement 
    });

    destinationInput.addEventListener('focus', function() {
        restoreSuggestions(destinationSuggestionsList, lastDestinationSuggestions);
    });

    // Fonction pour restaurer les suggestions
    function restoreSuggestions(suggestionsElement, suggestions) {
        suggestionsElement.innerHTML = '';
        suggestions.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item.display_name;
            li.addEventListener('click', function() {
                if (suggestionsElement === departureSuggestionsList) {
                    departureInput.value = item.display_name;
                    const departureAddressElement = document.getElementById('selected-departure-address');
                    if (departureAddressElement) {
                        departureAddressElement.value = item.display_name;
                    }
                    isDepartureValid = true; // Définir l'indicateur sur vrai pour un départ valide
                } else {
                    destinationInput.value = item.display_name;
                    const destinationAddressElement = document.getElementById('selected-destination-address');
                    if (destinationAddressElement) {
                        destinationAddressElement.value = item.display_name;
                    }
                    isDestinationValid = true; // Définir l'indicateur sur vrai pour une destination valide
                }
                suggestionsElement.innerHTML = ''; // Effacer les suggestions
                validateAddresses(); // Valider les adresses après sélection

                // Ajouter un marqueur à la carte
                const latLng = [item.lat, item.lon];
                if (suggestionsElement === departureSuggestionsList) {
                    if (departureMarker) map.removeLayer(departureMarker);
                    departureMarker = L.marker(latLng).addTo(map);
                    map.flyTo(latLng, 15); // Centrer la carte sur le nouveau marqueur
                } else {
                    if (destinationMarker) map.removeLayer(destinationMarker);
                    destinationMarker = L.marker(latLng).addTo(map);
                }

                // Calculer l'itinéraire si les deux marqueurs sont définis
                if (departureMarker && destinationMarker) {
                    calculateRoute(departureMarker.getLatLng(), destinationMarker.getLatLng());
                }
            });
            suggestionsElement.appendChild(li);
        });
    }

    // Fonction pour calculer et afficher l'itinéraire
    function calculateRoute(start, end) {
        if (routingControl) {
            map.removeControl(routingControl); // Supprimez l'ancien contrôle de l'itinéraire
        }
        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(start),
                L.latLng(end)
            ],
            routeWhileDragging: true,
            createMarker: function() { return null; } // Désactiver les marqueurs par défaut
        }).on('routesfound', function(f) {
            var routes = f.routes; // Récupérez les itinéraires
            if (routes.length > 0) {
                var summary = routes[0].summary; // Récupérez le sommaire de l'itinéraire

                // Convertir le temps total de secondes en heures et minutes
                var totalTime = summary.totalTime;
                var hours = Math.floor(totalTime / 3600);
                var minutes = Math.floor((totalTime % 3600) / 60);

                let tarifTest = 0.5;

                // Mettre à jour l'élément des étapes de l'itinéraire avec la distance et le temps formatés
                // toFixed(nb de chiffres après la virgule)
                document.getElementById('itinerary-steps').innerHTML = `
                    <div>Distance : ${(summary.totalDistance / 1000).toFixed(1)} km</div> 
                    <div>Temps de trajet estimé : ${hours} heures et ${minutes} minutes</div>
                    <div>Tarif : ${(summary.totalDistance / 1000).toFixed(1) * tarifTest} €
                `;

                // Ajuster la carte pour afficher les deux marqueurs avec une marge
                var group = L.featureGroup([departureMarker, destinationMarker]);
                map.fitBounds(group.getBounds(), { padding: [50, 50] }); 
            } else {
                console.log('Aucun itinéraire trouvé');
            }
        }).addTo(map);
    }
    
});
