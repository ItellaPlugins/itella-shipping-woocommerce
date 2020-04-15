'use strict';

if (document.getElementById('shipping_method_0_itella_pp').checked) {
	init();
}

function init() {

		// adding to global for easier debugging, pass in root element where to build all HTML
		window.itella = new itellaMapping(document.getElementById('shipping_method_0_itella_pp').nextSibling);

		let terminals = [];
		//.filter(terminal=>terminal.partnerType == 'POSTI')
		//.map(terminal=>{return {id: terminal.id, location: terminal.location, publicName: terminal.publicName}});

		itella
			// set base url where images are placed
			.setImagesUrl(`${mapScript.pluginsUrl}/assets/images/`)
			// configure translation
			.setStrings({nothing_found: 'Nieko nerasta', modal_header: 'Taškai'})
			// build HTML and register event handlers
			.init()

			// for search to work properly country iso2 code must be set (defaults to LT), empty string would allow global search
			.setCountry('LT')
			// configure pickup points data (must adhere to pickup point data from itella-api)
			.setLocations(terminals, true)
			// to register function that does something when point is selected
			.registerCallback(function (manual) {
				// this gives full access to itella class
				// console.log('is manual', manual); // tells if it was human interaction
				localStorage.setItem('pickupPoint', JSON.stringify({
					'id': this.selectedPoint.id,
				}));
				updateHiddenPpIdInput(this.selectedPoint.id);
			});

		setHiddenPpIdInput();

		// load locations
		var oReq = new XMLHttpRequest();
		/* access itella class inside response handler */
		oReq.itella = itella;
		oReq.addEventListener('loadend', loadJson);
		oReq.open('GET', `${mapScript.pluginsUrl}/../../locations/locationsLT.json`);
		oReq.send();

}


function loadJson() {
	let json = JSON.parse(this.responseText);
	this.itella.setLocations(json, true);

	if (localStorage.getItem('pickupPoint')) {
		const pickupPoint = JSON.parse(localStorage.getItem('pickupPoint'));
		// select from list by pickup point ID
		itella.setSelection(pickupPoint.id, false); // LT smartpost
	}
}

function setHiddenPpIdInput() {
	const radio = document.getElementById('shipping_method_0_itella_pp');
	const ppIdElement = document.createElement('input');
	ppIdElement.setAttribute('type', 'hidden');
	ppIdElement.setAttribute('name', 'itella-chosen-point-id');
	ppIdElement.setAttribute('id', 'itella-chosen-point-id');
	radio.parentElement.appendChild(ppIdElement);
	if (localStorage.getItem('pickupPoint')) {
		const pickupPoint = JSON.parse(localStorage.getItem('pickupPoint'));
		ppIdElement.value = pickupPoint.id;
	}
}

function updateHiddenPpIdInput(ppId) {
	const ppIdElement = document.getElementById('itella-chosen-point-id');
	ppIdElement.value = ppId;
}