import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('planning');

    var calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin],
        initialView: 'dayGridMonth',
        locale: 'fr',
        firstDay: 1,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: {
            today: 'Aujourd\'hui',
            month: 'Mois',
            week: 'Semaine',
            list: 'Liste'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('/chemin/vers/api/events', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => successCallback(data))
            .catch(error => failureCallback(error));
        },
        dateClick: function(info) {
            console.log('Date cliquée: ', info.dateStr);
        },
        eventClick: function(info) {
            console.log('Événement cliqué: ', info.event.title);
        }
    });

    calendar.render();
});
