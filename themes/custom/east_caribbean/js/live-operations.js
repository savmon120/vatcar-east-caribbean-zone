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

            const eczConfig = settings.eczVatsim || {};
            const feedUrl = eczConfig.feedUrl || 'https://data.vatsim.net/v3/vatsim-data.json';
            const refreshRate = (eczConfig.refreshRate || 60) * 1000;

            const rawPrefixes = eczConfig.prefixes || 'TNCA, TNCB, TNCC, TNCM, TQPF, TNCS, TNCE, TNCF, TBPB, TFFF, TFFR, TAPA, TGPY, TVSA, TLPL, TDPD, TKPK, TTPP';
            const targetPrefixes = rawPrefixes.split(',').map(prefix => prefix.trim().toUpperCase()).filter(p => p.length > 0);

            async function fetchVatsimData() {
                try {
                    const response = await fetch(feedUrl);
                    if (!response.ok) throw new Error("Network response was not ok");
                    const data = await response.json();

                    if (liveList) {
                        liveList.innerHTML = '';
                        if (data.controllers && Array.isArray(data.controllers)) {
                            const activeControllers = data.controllers.filter(controller => {
                                if (!controller.callsign) return false;
                                const matchesPrefix = targetPrefixes.some(prefix => controller.callsign.toUpperCase().startsWith(prefix));
                                const isNotObserver = !controller.callsign.endsWith('_OBS') && controller.facility !== 0;
                                return matchesPrefix && isNotObserver;
                            });

                            if (activeControllers.length === 0) {
                                liveList.innerHTML = '<li class="ops-empty">No controllers currently online.</li>';
                            } else {
                                activeControllers.forEach(controller => {
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
                    }

                    if (inboundsList && outboundsList) {
                        inboundsList.innerHTML = '';
                        outboundsList.innerHTML = '';

                        const inbounds = [];
                        const outbounds = [];

                        if (data.pilots && Array.isArray(data.pilots)) {
                            data.pilots.forEach(pilot => {
                                if (!pilot.flight_plan) return;

                                const dep = pilot.flight_plan.departure || '';
                                const arr = pilot.flight_plan.arrival || '';

                                const isArrival = targetPrefixes.some(prefix => arr.toUpperCase().startsWith(prefix));
                                const isDeparture = targetPrefixes.some(prefix => dep.toUpperCase().startsWith(prefix));

                                if (isArrival) inbounds.push(pilot);
                                if (isDeparture) outbounds.push(pilot);
                            });
                        }

                        if (inbounds.length === 0) {
                            inboundsList.innerHTML = '<li class="flight-empty">No inbound flights tracked.</li>';
                        } else {
                            inbounds.forEach(pilot => {
                                const li = document.createElement('li');
                                const acft = pilot.flight_plan.aircraft ? pilot.flight_plan.aircraft.split('/')[0].substring(0, 8) : 'N/A';

                                li.innerHTML = `
                            <span class="f-callsign">${pilot.callsign}</span>
                            <span class="f-pilot hide-mobile">${pilot.name}</span>
                            <span class="f-acft hide-mobile">${acft}</span>
                            <span class="f-dep">${pilot.flight_plan.departure}</span>
                            <span class="f-arr">${pilot.flight_plan.arrival}</span>
                        `;
                                inboundsList.appendChild(li);
                            });
                        }

                        if (outbounds.length === 0) {
                            outboundsList.innerHTML = '<li class="flight-empty">No outbound flights tracked.</li>';
                        } else {
                            outbounds.forEach(pilot => {
                                const li = document.createElement('li');
                                const acft = pilot.flight_plan.aircraft ? pilot.flight_plan.aircraft.split('/')[0].substring(0, 8) : 'N/A';

                                li.innerHTML = `
                            <span class="f-callsign">${pilot.callsign}</span>
                            <span class="f-pilot hide-mobile">${pilot.name}</span>
                            <span class="f-acft hide-mobile">${acft}</span>
                            <span class="f-dep">${pilot.flight_plan.departure}</span>
                            <span class="f-arr">${pilot.flight_plan.arrival}</span>
                        `;
                                outboundsList.appendChild(li);
                            });
                        }
                    }

                } catch (error) {
                    console.error("Error fetching VATSIM data:", error);
                    if (liveList) liveList.innerHTML = '<li class="ops-error">Unable to load radar data.</li>';
                    if (inboundsList) inboundsList.innerHTML = '<li class="flight-error">Unable to load flight data.</li>';
                    if (outboundsList) outboundsList.innerHTML = '<li class="flight-error">Unable to load flight data.</li>';
                }
            }

            async function fetchBookings() {
                if (!bookedList) return;

                try {
                    // TODO: Bookings endpoint
                    bookedList.innerHTML = '<li class="ops-empty">No upcoming bookings.</li>';

                } catch (error) {
                    console.error("Error fetching bookings:", error);
                    bookedList.innerHTML = '<li class="ops-error">Unable to load bookings.</li>';
                }
            }

            fetchVatsimData();
            fetchBookings();

            setInterval(() => {
                fetchVatsimData();
                fetchBookings();
            }, refreshRate);
        }
    };
})(Drupal, drupalSettings);