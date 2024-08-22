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
    let lastDepartureSuggestions = [];
    let lastDestinationSuggestions = [];



    // Récupération des éléments d'interface pour les entrées de texte et les suggestions
    let departureInput = document.getElementById('departure');
    let destinationInput = document.getElementById('destination');
    let departureSuggestionsList = document.getElementById('departure-suggestions');
    let destinationSuggestionsList = document.getElementById('destination-suggestions');
    let itineraryDiv = document.getElementById('itinerary');
    const startLatInput = document.getElementById('startLat');
    const startLngInput = document.getElementById('startLng');
    const endLatInput = document.getElementById('endLat');
    const endLngInput = document.getElementById('endLng');
    const startAdresse = document.getElementById('startAddress');
    const endAdresse = document.getElementById('endAddress');

    // Indicateurs pour vérifier si des suggestions valides sont sélectionnées
    let isDepartureValid = false;
    let isDestinationValid = false;

    // Fonction pour afficher le bloc d'info de course
    function validateAddresses() {
        if (isDepartureValid && isDestinationValid) {
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
                

                const latLng = [item.lat, item.lon]; // Récupérer les coordonnées de l'adresse sélectionnée
            
                if (isDeparture) { //si le depart est valid, recuperation des ID necessaires
                    const departureAddressElement = document.getElementById('selected-departure-address');
                    const startLatInput = document.getElementById('startLat');
                    const startLngInput = document.getElementById('startLng');
    
                    if (departureAddressElement) { 
                        departureAddressElement.value = item.display_name; // Mettre à jour le champ d'adresse
                        startAdresse.value = item.display_name;
                        console.log('adresse de depart: ' , startAdresse.value);
                    }
                    
                    // Stocker les coordonnées dans les champs cachés
                    if (startLatInput && startLngInput) {
                        startLatInput.value = item.lat;
                        startLngInput.value = item.lon;
                    }else{
                        // console.error('Les éléments de départ ne sont pas présents!');//debbug
                    }
    
                    isDepartureValid = true; // Définir l'indicateur sur vrai pour un départ valide
    
                    if (departureMarker) map.removeLayer(departureMarker); // Supprimer l'ancien marqueur de départ
                    departureMarker = L.marker(latLng).addTo(map); // Ajouter un nouveau marqueur de départ
                    map.flyTo(latLng, 15); // Centrer la carte sur le nouveau marqueur
    
                } else {
                    const destinationAddressElement = document.getElementById('selected-destination-address');
                    const endLatInput = document.getElementById('endLat');
                    const endLngInput = document.getElementById('endLng');
                    endAdresse.value = item.display_name;
    
                    if (destinationAddressElement) {
                        destinationAddressElement.value = item.display_name;
                        endAdresse.value = item.display_name;
                        console.log( 'adresse de destination: ' , endAdresse.value);
                    }
                    
                    // Stocker les coordonnées dans les champs cachés
                    if (endLatInput && endLngInput) {
                        endLatInput.value = item.lat;
                        endLngInput.value = item.lon;
                    }else{
                        // console.error('Les éléments de destination ne sont pas présents!');//debbug
                    }
    
                    isDestinationValid = true; // Définir l'indicateur sur vrai pour une destination valide
    
                    if (destinationMarker) map.removeLayer(destinationMarker);
                    destinationMarker = L.marker(latLng).addTo(map);
                }
                if( startLatInput, startLngInput){
                    console.log('Les éléments sont présents!' , startLatInput, startLngInput);
                }else{
                    console.error('Les éléments ne sont pas présents!')
                }
                if( endLatInput, endLngInput){
                    console.log('Les éléments sont présents!' , endLatInput, endLngInput);
                }else{
                    console.error('Les éléments ne sont pas présents!')
                }
               


                // Calculer l'itinéraire si les deux marqueurs sont définis
                if (departureMarker && destinationMarker) {
                    calculateRoute(departureMarker.getLatLng(), destinationMarker.getLatLng());
                }
    
                // Valider les adresses après sélection
                validateAddresses(); 
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
    function handleInput(inputElement, suggestionsElement, isDeparture) {// boolean pour le départ ou la destination
        const query = inputElement.value;// Rechercher les suggestions correspondant à la requête
        if (query.length > 2) {// Si la requête est plus grande que 2 caractères
            fetch(`https://nominatim.openstreetmap.org/search?q=${query}&format=json`)// Requête vers le service de recherche
                .then(response => response.json())// Transformer la réponse en JSON
                .then(data => {
                    addSuggestions(inputElement, suggestionsElement, data, isDeparture);// Ajouter les suggestions
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
            routeWhileDragging: true,// Définir sur true pour permettre le déplacement du marqueur pendant le calcul de l'itinéraire
            createMarker: function() { return null; } // Désactiver les marqueurs par défaut
        }).on('routesfound', function(f) { // Fonction appelée lorsque les itinéraires sont trouvés
            var routes = f.routes; // Récupérez les itinéraires
            if (routes.length > 0) {
                var summary = routes[0].summary; // Récupérez le sommaire de l'itinéraire

                // Convertir le temps total de secondes en heures et minutes
                var totalTime = summary.totalTime;
                var hours = Math.floor(totalTime / 3600);
                var minutes = Math.floor((totalTime % 3600) / 60);

                let tarifTest = (summary.totalDistance / 1000) * 0.5;

                // Mettre à jour l'élément des étapes de l'itinéraire avec la distance et le temps formatés
                // toFixed(nb de chiffres après la virgule)
                document.getElementById('itinerary-steps').innerHTML = `
                    <div><p>Distance : ${(summary.totalDistance / 1000).toFixed(1)} km</p></div> 
                    <div></p>Temps de trajet estimé : ${hours} heures et ${minutes} minutes</p></div>
                    <div><p>Tarif : ${tarifTest.toFixed(1)} €</p></div>
                `;

                const clientTarif = document.getElementById('clientTarif');
                const clientDistance = document.getElementById('clientDistance');
                const clientDuration = document.getElementById('clientDuration');
                clientTarif.value=tarifTest;
                clientDistance.value=routes[0].summary.totalDistance;
                clientDuration.value=routes[0].summary.totalTime;


                // Ajuster la carte pour afficher les deux marqueurs avec une marge
                var group = L.featureGroup([departureMarker, destinationMarker]);
                map.fitBounds(group.getBounds(), { padding: [50, 50] }); 
            } else {
                console.log('Aucun itinéraire trouvé');
            }
        }).addTo(map);
    }
    
});
