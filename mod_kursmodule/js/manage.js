// This file is part of Moodle - http://moodle.org/
//
// Einfaches, abhaengigkeitsfreies Drag & Drop fuer die Kursmodule-
// Verwaltungsseite (native HTML5 Drag&Drop API, kein jQuery/Sortable.js
// noetig). Persistiert die neue Reihenfolge per fetch() gegen ajax.php.

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var list = document.getElementById('kursmodule-link-list');
        if (!list) {
            return;
        }

        var cmid = list.getAttribute('data-cmid');
        var sesskey = list.getAttribute('data-sesskey');
        var reorderurl = list.getAttribute('data-reorderurl');
        var dragged = null;

        list.addEventListener('dragstart', function(e) {
            var row = e.target.closest('.kursmodule-link-row');
            if (!row) {
                return;
            }
            dragged = row;
            row.classList.add('kursmodule-dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        list.addEventListener('dragend', function() {
            if (dragged) {
                dragged.classList.remove('kursmodule-dragging');
            }
            dragged = null;
            persistOrder();
        });

        list.addEventListener('dragover', function(e) {
            e.preventDefault();
            var row = e.target.closest('.kursmodule-link-row');
            if (!row || row === dragged || !dragged) {
                return;
            }
            var rect = row.getBoundingClientRect();
            var before = (e.clientY - rect.top) < (rect.height / 2);
            list.insertBefore(dragged, before ? row : row.nextSibling);
        });

        function persistOrder() {
            var ids = [];
            list.querySelectorAll('.kursmodule-link-row').forEach(function(row) {
                ids.push(parseInt(row.getAttribute('data-linkid'), 10));
            });

            var params = new URLSearchParams();
            params.set('action', 'reorder');
            params.set('id', cmid);
            params.set('sesskey', sesskey);
            params.set('order', JSON.stringify(ids));

            fetch(reorderurl + '?' + params.toString(), {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: params.toString()
            }).catch(function() {
                // Netzwerkfehler: Reihenfolge bleibt clientseitig sichtbar,
                // beim naechsten Laden gilt wieder der zuletzt gespeicherte Stand.
            });
        }
    });
})();
