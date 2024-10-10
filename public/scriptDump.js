// fonction pour envoyer les adresse via ajax

function sendAdresses() {
    const departureAddress = document.getElementById('selected-departure-address');
    const destinationAddress = document.getElementById('selected-destination-address');
    departureAddress.value =document.getElementById('departure').value;
    destinationAddress.value =document.getElementById('destination').value;

    // Envoyer une requête AJAX au serveur
    fetch('/StrasVTC/getAdresses', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ departureAddress, destinationAddress })// Ajouter les adresses de départ et de destination
    })
    .then(response => response.json())
    .then(data => {
        // Gérer la réponse du serveur ici
        console.log(data);
    })
    .catch(error => {
        console.error('Erreur :', error);
    });
}
//window pour s'assurer que la fonction est appelée après la page est chargée
window.sendAdresses = sendAdresses;  

// Plus ca 

function validateAndSendAddresses() {
    if (validateForm()) {
        sendAdresses();
        return true;
    }
    return false;
}





function editVehicleInfo(vehiculeId) {
    const block = document.getElementById(`vehicule-info-${vehiculeId}`);
    const btnContainer = document.querySelector(`#modify-vehicle-btn-${vehiculeId}`).parentNode;

    const editableElements = [
        document.getElementById(`vehicule-name-${vehiculeId}`),
        document.getElementById(`vehicule-marque-${vehiculeId}`)
    ];

    // Rendre les éléments éditables
    editableElements.forEach(element => {
        if (element) {
            element.contentEditable = true;
            element.style.backgroundColor = 'white';
            element.style.border = '1px solid #ccc';
            element.style.padding = '1px';
            element.style.borderRadius = '3px';
        }
    });

    // Gestion des catégories
    const categorieElement = document.getElementById(`vehicule-categorie-${vehiculeId}`);
    const originalCategorie = categorieElement.textContent.trim();
    const selectCategorie = document.createElement('select');
    selectCategorie.id = `vehicule-categorie-select-${vehiculeId}`;

    const optionVan = new Option('Van', 'Van');
    const optionBerline = new Option('Berline', 'Berline');
    selectCategorie.add(optionVan);
    selectCategorie.add(optionBerline);
    selectCategorie.value = originalCategorie;

    categorieElement.replaceWith(selectCategorie);

    const nbPlaceElement = document.getElementById(`vehicule-nbPlace-${vehiculeId}`);
    const originalNbPlace = nbPlaceElement.textContent.trim();
    const selectNbPlace = document.createElement('select');
    selectNbPlace.id = `vehicule-nbPlace-select-${vehiculeId}`;

    function updateNbPlaceOptions() {
        selectNbPlace.innerHTML = '';
        const maxPlaces = selectCategorie.value === 'Van' ? 7 : 4;
        for (let i = 1; i <= maxPlaces; i++) {
            const option = new Option(i, i);
            selectNbPlace.add(option);
        }
        selectNbPlace.value = originalNbPlace;
    }

    updateNbPlaceOptions();

    nbPlaceElement.replaceWith(selectNbPlace);

    selectCategorie.addEventListener('change', updateNbPlaceOptions);

    // Ajouter le champ d'upload pour l'image
    let imageUpload = document.getElementById(`vehicule-image-upload-${vehiculeId}`);
    if (!imageUpload) {
        imageUpload = document.createElement('input');
        imageUpload.type = 'file';
        imageUpload.accept = 'image/*'; 
        imageUpload.id = `vehicule-image-upload-${vehiculeId}`;
        block.appendChild(imageUpload);
    }

    const modifyBtn = document.getElementById(`modify-vehicle-btn-${vehiculeId}`);
    modifyBtn.textContent = 'Valider';

    const newModifyBtn = modifyBtn.cloneNode(true);
    modifyBtn.replaceWith(newModifyBtn);

    newModifyBtn.addEventListener('click', () => {
        const updatedDetails = {
            nom: document.getElementById(`vehicule-name-${vehiculeId}`).textContent.trim(),
            marque: document.getElementById(`vehicule-marque-${vehiculeId}`).textContent.trim(),
            categorie: selectCategorie.value,
            nbPlace: selectNbPlace.value
        };

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ path('app_chauffeur_edit_vehicule', {'id': chauffeur.id}) }}";
        form.enctype = 'multipart/form-data'; 

        for (const [key, value] of Object.entries(updatedDetails)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_csrf_token';
        csrfToken.value = "{{ csrf_token('vehicule_edit') }}";
        form.appendChild(csrfToken);

        const vehiculeIdInput = document.createElement('input');
        vehiculeIdInput.type = 'hidden';
        vehiculeIdInput.name = 'vehicule_id';
        vehiculeIdInput.value = vehiculeId;
        form.appendChild(vehiculeIdInput);

        // Ajouter le champ d'upload sans le déplacer
        if (imageUpload.files.length > 0) {
            form.appendChild(imageUpload);
        }

        document.body.appendChild(form);
        form.submit();
    });
}