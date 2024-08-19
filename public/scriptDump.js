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