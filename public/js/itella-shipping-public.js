'use strict';

if (document.getElementById('shipping_method_0_itella_pp').checked) {
	init();
}

function init() {

	// console.log(itellaShippingMethod)

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
				// console.log(this.selectedPoint);
				localStorage.setItem('pickupPoint', JSON.stringify({
					'id': this.selectedPoint.id,
					'publicName': this.selectedPoint.publicName
				}));
			});

		// load demo locations
		var oReq = new XMLHttpRequest();
		/* access itella class inside response handler */
		oReq.itella = itella;
		oReq.addEventListener('loadend', loadJson);
		oReq.open('GET', `${mapScript.pluginsUrl}/assets/example/locations_lt.json`);
		oReq.send();

	// set radio id and selected pp name if previously selected
	if (localStorage.getItem('pickupPoint')) {
		const pickupPoint = JSON.parse(localStorage.getItem('pickupPoint'));
		const selectedEl = document.querySelector('.itella-chosen-point');
		selectedEl.innerText = pickupPoint.publicName;
		const radio = document.getElementById('shipping_method_0_itella_pp');
		const inputPpId = document.createElement('input');
		inputPpId.setAttribute('type', 'hidden');
		inputPpId.setAttribute('name', 'itella-chosen-point-id');
		inputPpId.value = pickupPoint.id;

		radio.parentElement.appendChild(inputPpId);
		// selectedEl.setAttribute('name', 'itella-chosen-point-id');
		// selectedEl.setAttribute('value', pickupPoint.id);
	}

}

function loadJson() {
	let json = JSON.parse(this.responseText);
	this.itella.setLocations(json, true);
	// select from list by pickup point ID
	// itella.setSelection(16058, false); // LT smartpost
}