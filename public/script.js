// charger le script apres que le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {

    const burger = document.querySelector('.burger');
    const mobileMenu = document.querySelector('.mobile_nav');

    burger.addEventListener('click', function() {
        burger.classList.toggle('active');
        mobileMenu.classList.toggle('active');
    });
    // Vérification si Leaflet est chargé
    if (typeof L === 'undefined') {
        console.error('Leaflet is not loaded!');//debug
        return;
    }

    /****************** Initialisation de la carte Leaflet ******************/
    var map = L.map('map').setView([48.58165, 7.7507], 13); // Carte centrée sur Paris

    // Ajout des tuiles OpenStreetMap à la carte
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

     /****************** Toggle des boutons de véhicule ******************/
     document.querySelectorAll('.vehicle-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Réinitialiser les boutons
            document.querySelectorAll('.vehicle-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Activer le bouton sélectionné
            this.classList.add('active');
        });
    });

    /****************** Déclaration des variables pour les marqueurs et itinéraire ******************/
    let departureMarker; // Marqueur de départ
    let destinationMarker; // Marqueur de destination
    let routingControl; // Variable pour le contrôle de l'itinéraire
    let lastDepartureSuggestions = []; // Dernières suggestions de départ
    let lastDestinationSuggestions = []; // Dernières suggestions de destination

    /****************** Récupération des éléments d'interface ******************/
    let departureInput = document.getElementById('departure'); // Champ d'entrée de l'adresse de départ
    let destinationInput = document.getElementById('destination'); // Champ d'entrée de l'adresse de destination
    let departureSuggestionsList = document.getElementById('departure-suggestions'); // Liste des suggestions de départ
    let destinationSuggestionsList = document.getElementById('destination-suggestions'); // Liste des suggestions de destination
    let itineraryDiv = document.getElementById('itinerary'); // Div pour afficher les détails de l'itinéraire
    const startLatInput = document.getElementById('startLat'); // Champ caché pour la latitude de départ
    const startLngInput = document.getElementById('startLng'); // Champ caché pour la longitude de départ
    const endLatInput = document.getElementById('endLat'); // Champ caché pour la latitude de destination
    const endLngInput = document.getElementById('endLng'); // Champ caché pour la longitude de destination
    const startAdresse = document.getElementById('startAddress'); // Champ caché pour l'adresse de départ
    const endAdresse = document.getElementById('endAddress'); // Champ caché pour l'adresse de destination
    var heptagone = [
        [48.96447, 7.62109],
        [48.85597, 8.05835],
        [48.52255, 8.09218],
        [48.24199, 7.66749],
        [48.24432, 7.42679],
        [48.46171, 7.20567],
        [48.7821, 7.23866]
    ];
    var restrictionHectagon = L.polygon(heptagone,{color: 'none'}).addTo(map);


    //  /****************** Fonction AJAX pour envoyer les coordonnées ******************/
    // function sendCoordinatesToServer(startLat, startLng, endLat, endLng, clientTarif, clientDistance, clientDuration) {
    //     fetch('/store-route-data', {
    //         method: 'POST',
    //         headers: {
    //             'Content-Type': 'application/json',
    //             'X-CSRF-Token': document.querySelector('input[name="_token"]').value
    //         },
    //         body: JSON.stringify({
    //             startLat: startLat,
    //             startLng: startLng,
    //             endLat: endLat,
    //             endLng: endLng,
    //             clientTarif: clientTarif,
    //             clientDistance: clientDistance,
    //             clientDuration: clientDuration
    //         })
    //     })
    //     .then(response => response.json())
    //     .then(data => {
    //         console.log('Coordonnées envoyées avec succès:', data);
    //         // Rediriger vers la prochaine étape ou afficher un message de succès si nécessaire
    //     })
    //     .catch(error => {
    //         console.error('Erreur lors de l\'envoi des coordonnées:', error);
    //     });
    // }

/****************** Fonction pour vérifier si un point est dans le polygone ******************/
    function isPointInPolygon(latLng) {
        return restrictionHectagon.getBounds().contains(latLng);
    }
    /****************** Validation des adresses ******************/
    let isDepartureValid = false;
    let isDestinationValid = false;

    function validateAddresses() {
        if (isDepartureValid && isDestinationValid) {
            itineraryDiv.style.display = 'block'; // Afficher les détails de l'itinéraire
        } else {
            // console.log('Une ou les deux adresses sont invalides.');
            itineraryDiv.style.display = 'none'; // Masquer l'itinéraire
        }
    }

    /****************** Gestion des suggestions ******************/
    function addSuggestions(inputElement, suggestionsElement, data, isDeparture) {
        // Stocker les dernières suggestions
        if (isDeparture) {// si la fonction est appelée pour les suggestions de départ
            lastDepartureSuggestions = data.slice(0, 5);// stocke les 5 premières suggestions
        } else {
            lastDestinationSuggestions = data.slice(0, 5);
        }

        // Vider les suggestions précédentes
        suggestionsElement.innerHTML = '';

        // Ajouter les nouvelles suggestions
        data.slice(0, 5).forEach(item => { // pour chaque élément de la liste
            const li = document.createElement('li');// crée un nouvel élément de liste
            li.textContent = item.display_name;// ajoute le texte de l'élément d'adresse à l'élément de liste
            li.addEventListener('click', function() {// ajoute un gestionnaire d'événement de clic
                inputElement.value = item.display_name;// met à jour la valeur de l'entrée avec le texte de l'élément d'adresse
                suggestionsElement.innerHTML = ''; // Effacer les suggestions pour si l'utilisateur clique sur une suggestion

                const latLng = [item.lat, item.lon]; // Récupérer les coordonnées de l'adresse sélectionnée
                
                
                // Vérifier si les coordonnées sont dans le polygone
                if (isDeparture) {
                    if (!isPointInPolygon(L.latLng(latLng))) {
                        alert('Cette adresse est en dehors de la zone autorisée.');
                        inputElement.value = ''; // Effacer la valeur de l'entrée
                        return; // Ne pas permettre la sélection de cette adresse
                    }
                    //ces 3 variables sont utilisées pour stocker les coordonnées de l'adresse sélectionnée(depart)
                    startAdresse.value = item.display_name;
                    startLatInput.value = item.lat;
                    startLngInput.value = item.lon;
                    isDepartureValid = true;
                    
                    // Mise à jour des marqueurs et validation selon l'adresse sélectionnée
                     //si un ancien marquer existe
                    if (departureMarker) map.removeLayer(departureMarker); // Supprimer l'ancien marqueur de départ
                    departureMarker = L.marker(latLng).addTo(map); // Ajouter le nouveau marqueur de départ
                    map.flyTo(latLng, 15); // Centrer la carte sur le nouveau marqueur
                } else {
                    // ces 3 variables sont utilisées pour stocker les coordonnées de l'adresse sélectionnée(destination)
                    endAdresse.value = item.display_name;// l'adresse au complet
                    endLatInput.value = item.lat;// latitude 
                    endLngInput.value = item.lon;//longitude
                    isDestinationValid = true;// definir le boolean sur Vrai

                    //si un ancien marquer existe
                    if (destinationMarker) map.removeLayer(destinationMarker); // Supprimer l'ancien marqueur de destination
                    destinationMarker = L.marker(latLng).addTo(map); // Ajouter le nouveau marqueur de destination
                }
                    console.log('adresse depart :', startAdresse.value
                            + ' latitude :' + startLatInput.value
                            + ' longitude :' + startLngInput.value
                            + ' adresse destination :' + endAdresse.value
                            + ' latitude :' + endLatInput.value
                            + ' longitude :' + endLngInput.value
                    );
                // Si les deux marqueurs sont définis, calculer l'itinéraire
                if (departureMarker && destinationMarker) {
                    //qui calcule l'itinéraire entre les deux marqueurs
                    calculateRoute(departureMarker.getLatLng(), destinationMarker.getLatLng());
                }

                // console.log('Adresse sélectionnée :', item.display_name, item.lat, item.lon);
                validateAddresses(); //appel la methode qui valide les adresses après sélection
            });
            // attribuer un "enfant" a la liste des suggestions
            suggestionsElement.appendChild(li);
        });
        // Gestion de la suppression de l'input
    inputElement.addEventListener('input', function() {
        if (inputElement.value === '') {
            // Si l'input est vide, supprimer le marqueur et réinitialiser les champs
            if (isDeparture) {
                if (departureMarker) {
                    map.removeLayer(departureMarker);
                    departureMarker = null;
                }
                startAdresse.value = '';
                startLatInput.value = '';
                startLngInput.value = '';
                isDepartureValid = false;
            } else {
                if (destinationMarker) {
                    map.removeLayer(destinationMarker);
                    destinationMarker = null;
                }
                endAdresse.value = '';
                endLatInput.value = '';
                endLngInput.value = '';
                isDestinationValid = false;
            }

            // Si les deux marqueurs sont supprimés, effacer l'itinéraire
            if (!departureMarker || !destinationMarker) {
                clearRoute(); // Une fonction à créer pour effacer l'itinéraire s'il existe
            }

            // Valider à nouveau les adresses, ainsi afficher ou non l'itineraire
            validateAddresses();
        }
    });
    }
    // mettre a jour l'itineraire selon les données
    function clearRoute() {
        if (routingControl) {
            map.removeControl(routingControl); // Supprimer l'ancien itinéraire s'il existe
            routingControl = null; // Réinitialiser la variable
        }
    
        // Vider les détails de l'itinéraire affichés à l'utilisateur
        document.getElementById('itinerary-steps').innerHTML = '';
        
        // Réinitialiser les champs cachés si nécessaire
        document.getElementById('clientTarif').value = '';
        document.getElementById('clientDistance').value = '';
        document.getElementById('clientDuration').value = '';
    }

    /****************** Gestion des entrées et suggestions ******************/
    departureInput.addEventListener('input', function() {
        //this = l'élément qui déclenche l'événement (departureInput)
        //departureSuggestionsList = la liste des suggestions
        //true = pour indiquer que c'est une suggestion de départ
        handleInput(this, departureSuggestionsList, true);
    });

    destinationInput.addEventListener('input', function() {
        //false = pour indiquer que c'est une suggestion de destination
        handleInput(this, destinationSuggestionsList, false);
    });

    function handleInput(inputElement, suggestionsElement, isDeparture) {
        const query = inputElement.value;
        if (query.length > 1) {// s'il existe au moins 1 caractère
            // Requête vers Nominatim pour obtenir les suggestions d'adresses
            fetch(`https://nominatim.openstreetmap.org/search?q=${query}&format=json`)
                .then(response => response.json())// récupérer les données au format JSON
                .then(data => {//faire appel à la fonction addSuggestions
                    addSuggestions(inputElement, suggestionsElement, data, isDeparture); // Ajouter les suggestions
                });
        }
    }

    /****************** Gestion du focus et blur sur les champs de texte ******************/
    departureInput.addEventListener('blur', function() {
        destinationSuggestionsList.style.display = 'block'; // affihcer les suggestions de destination
        setTimeout(() => departureSuggestionsList.innerHTML = '', 100); // Délai pour permettre l'enregistrement de l'événement
    });
    
    departureInput.addEventListener('focus', function() {
        destinationSuggestionsList.style.display = 'none'; // Masquer les suggestions de destination
        restoreSuggestions(departureSuggestionsList, lastDepartureSuggestions); // Restaurer les suggestions
    });

    destinationInput.addEventListener('blur', function() {
        departureSuggestionsList.style.display = 'block'; // affihcer les suggestions de destination
        setTimeout(() => destinationSuggestionsList.innerHTML = '', 100); // Délai pour permettre l'enregistrement de l'événement
    });

    destinationInput.addEventListener('focus', function() {
        departureSuggestionsList.style.display = 'none'; // Masquer les suggestions de départ
        restoreSuggestions(destinationSuggestionsList, lastDestinationSuggestions); // Restaurer les suggestions
    });

    /****************** Restauration des suggestions ******************/
    function restoreSuggestions(suggestionsElement, suggestions) {
        suggestionsElement.innerHTML = '';
        suggestions.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item.display_name;
            li.addEventListener('click', function() {

                const latLng = [item.lat, item.lon];
                // Vérifier si les coordonnées sont dans le polygone
                if (suggestionsElement === departureSuggestionsList) {
                    if (!isPointInPolygon(L.latLng(latLng))) {
                        alert('L\'adresse de départ est en dehors de la zone autorisée.');
                        return;
                    }
                    departureInput.value = item.display_name;
                    isDepartureValid = true;
                } else {
                    destinationInput.value = item.display_name;
                    isDestinationValid = true;
                }

                suggestionsElement.innerHTML = ''; // Effacer les suggestions
                validateAddresses(); // Valider les adresses après sélection

                // Ajouter un marqueur à la carte selon l'adresse sélectionnée

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
   

    /****************** Calcul et affichage de l'itinéraire ******************/
    function calculateRoute(start, end) {
        if (routingControl) {
            map.removeControl(routingControl); // Supprimer l'ancien itinéraire si existe
        }
        const plan = L.Routing.plan(start, end, {
            routeWhileDragging: false, //ne pas permettre le déplacement des marqueurs pendant le calcul
            addWaypoints: false, // Désactiver la création de points de passage
            draggableWaypoints: false, // Désactiver le déplacement des points de passage
            createMarker: function() { return null; } // Désactiver la creation de marquers au drag des marqueurs
        });
        // L. provient de la bibliothèque Leaflet, nottament de Leaflet-Routing-Machine
        routingControl = L.Routing.control({// Créer un nouveau contrôle d'itinéraire
            waypoints: [ // Définir les points de départ et d'arrivée
                L.latLng(start),// Départ
                L.latLng(end)// Arrivée
            ],
             // Créer un plan d'itinéraire
            routeWhileDragging: false, //ne pas permettre le déplacement des marqueurs pendant le calcul
            addWaypoints: false, // Désactiver la création de points de passage
            createMarker: function() { return null; } // Désactiver la creation de marquers au drag des marqueurs
            // routesfound est un evenement de Leaflet-Routing-Machine, qui est déclenché lorsque l'itinéraire est trouvé
            // found, fonction callback, appelée lorsque l'itinéraire est trouvé
        }).on('routesfound', function(found) {
            // found est un objet qui contient les informations sur l'itinéraire trouvé
            var routes = found.routes;//
            if (routes.length > 0) {// Vérifier si des itinéraires ont été trouvés
                var summary = routes[0].summary;//recuperer le premier sommaire de l'itinéraire
                
                console.log('coordones depart', start,
                    'coordones arrive', end);
                // Calcul du temps de trajet estimé et du tarif
                var totalTime = summary.totalTime;
                //Math.floor permet de récupérer la partie entière d'un nombre
                var hours = Math.floor(totalTime / 3600);
                var minutes = Math.floor((totalTime % 3600) / 60);
                // Calcul du tarif
                let tarifTest = (summary.totalDistance / 1000) * 0.5;

                // Mise à jour des détails de l'itinéraire
                document.getElementById('itinerary-steps').innerHTML = `
                    <p>Distance : ${(summary.totalDistance / 1000).toFixed(1)} km</p>
                    <p>Temps de trajet estimé : ${hours} heures et ${minutes} minutes</p>
                    <p>Tarif : ${tarifTest.toFixed(1)} €</p>
                `;

                // Mise à jour des champs cachés avec les valeurs calculées
                const clientTarif = document.getElementById('clientTarif');
                const clientDistance = document.getElementById('clientDistance');
                const clientDuration = document.getElementById('clientDuration');
                clientTarif.value = tarifTest;
                clientDistance.value = routes[0].summary.totalDistance;
                clientDuration.value = routes[0].summary.totalTime;

                // Ajustement de la vue pour inclure les deux marqueurs
                var group = L.featureGroup([departureMarker, destinationMarker]);
                map.fitBounds(group.getBounds(), { padding: [50, 50] }); 
            } else {
                // console.log('Aucun itinéraire trouvé');
            }
        }).addTo(map);    
    }
    
});
