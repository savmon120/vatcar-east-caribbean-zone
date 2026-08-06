(function (Drupal, drupalSettings) {
    'use strict';

    Drupal.behaviors.eczLiveOperations = {
        attach: function (context, settings) {
            const liveList = context.querySelector('#live-controllers-list');
            const inboundsList = context.querySelector('#live-inbounds-list');
            const outboundsList = context.querySelector('#live-outbounds-list');
            const bookedList = context.querySelector('#booked-controllers-list');

            const wrapper = context.querySelector('.live-operations-card') || context.querySelector('.live-flights-card');
            if (!wrapper || wrapper.dataset.initialized) {
                return;
            }
            wrapper.dataset.initialized = 'true';

            const feedUrl = '/api/vatsim/live';
            const refreshRate = 60 * 1000;

            async function fetchVatsimData() {
                try {
                    const response = await fetch(feedUrl);
                    if (!response.ok) throw new Error("Network response was not ok");
                    const data = await response.json();

                    if (liveList) {
                        liveList.innerHTML = '';
                        if (!data.controllers || data.controllers.length === 0) {
                            liveList.innerHTML = '<li class="ops-empty">No controllers currently online.</li>';
                        } else {
                            data.controllers.forEach(controller => {
                                const li = document.createElement('li');
                                li.innerHTML = `
                                    <div class="ops-controller-info">
                                        <span class="ops-station">${controller.callsign}</span>
                                        <span class="ops-name">${controller.name}</span>
                                    </div>
                                    <span class="ops-freq">${controller.frequency}</span>
                                `;
                                liveList.appendChild(li);
                            });
                        }
                    }

                    if (inboundsList) {
                        inboundsList.innerHTML = '';
                        if (!data.inbounds || data.inbounds.length === 0) {
                            inboundsList.innerHTML = '<li class="flight-empty">No inbound flights tracked.</li>';
                        } else {
                            data.inbounds.forEach(pilot => {
                                const li = document.createElement('li');
                                li.innerHTML = `
                                    <span class="f-callsign">${pilot.callsign}</span>
                                    <span class="f-pilot hide-mobile">${pilot.name}</span>
                                    <span class="f-acft hide-mobile">${pilot.aircraft}</span>
                                    <span class="f-dep">${pilot.departure}</span>
                                    <span class="f-arr">${pilot.arrival}</span>
                                `;
                                inboundsList.appendChild(li);
                            });
                        }
                    }

                    if (outboundsList) {
                        outboundsList.innerHTML = '';
                        if (!data.outbounds || data.outbounds.length === 0) {
                            outboundsList.innerHTML = '<li class="flight-empty">No outbound flights tracked.</li>';
                        } else {
                            data.outbounds.forEach(pilot => {
                                const li = document.createElement('li');
                                li.innerHTML = `
                                    <span class="f-callsign">${pilot.callsign}</span>
                                    <span class="f-pilot hide-mobile">${pilot.name}</span>
                                    <span class="f-acft hide-mobile">${pilot.aircraft}</span>
                                    <span class="f-dep">${pilot.departure}</span>
                                    <span class="f-arr">${pilot.arrival}</span>
                                `;
                                outboundsList.appendChild(li);
                            });
                        }
                    }

                } catch (error) {
                    console.error("Error fetching VATSIM data:", error);
                }
            }

            async function fetchBookings() {
                if (!bookedList) return;
                try {
                    bookedList.innerHTML = '<li class="ops-empty">No upcoming bookings.</li>';
                } catch (error) {
                    console.error("Error fetching bookings:", error);
                }
            }

            let intervalId = null;

            function startPolling() {
                fetchVatsimData();
                fetchBookings();
                if (!intervalId) {
                    intervalId = setInterval(() => {
                        fetchVatsimData();
                        fetchBookings();
                    }, refreshRate);
                }
            }

            function stopPolling() {
                if (intervalId) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
            }

            startPolling();

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stopPolling();
                } else {
                    startPolling();
                }
            });
        }
    };
})(Drupal, drupalSettings);