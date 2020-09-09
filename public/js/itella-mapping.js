"use strict";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var itellaMapping = /*#__PURE__*/function () {
  function itellaMapping(el) {
    _classCallCheck(this, itellaMapping);

    /* Itella Mapping version */
    this.version = '1.2.3';
    this._isDebug = false;
    /* default map center Lithuania Kaunas */

    this._defaultMapPos = [54.890926, 23.919338];
    /* zoom levels for map */

    this.ZOOM_DEFAULT = 8;
    this.ZOOM_SELECTED = 13;
    this.ZOOM_MAX = 18;
    this.ZOOM_MIN = 4;
    /**
     * Element where to mount Itella Mapping
     * @type HTMLElement
     */

    this.mount = el;
    /* Leaflet elements */

    this._map = null;
    this._pickupIcon = null;
    this._markerLayer = null;
    this._latlongArray = [];
    /* Pointers to often used modal elements */

    this.UI = {};
    this.UI.container = null;
    this.UI.modal = null;
    this.images_url = '';
    this.strings = {
      modal_header: 'Pickup points',
      selector_header: 'Pickup point',
      workhours_header: 'Workhours',
      contacts_header: 'Contacts',
      search_placeholder: 'Enter postcode/address',
      select_pickup_point: 'Select a pickup point',
      no_pickup_points: 'No points to select',
      select_btn: 'select',
      back_to_list_btn: 'reset search',
      nothing_found: 'Nothing found',
      select_pickup_point_btn: 'Select pickup point',
      no_information: 'No information',
      error_leaflet: 'Leaflet is required for Itella-Mapping',
      error_missing_mount_el: 'No mount supplied to itellaShipping'
    };
    this.country = 'LT';
    /* Functions to run after point is selected */

    this.callbackList = [];
    /* Selected pickup */

    this.selectedPoint = null;
    this.locations = [];
    this._locations = [];
    this._isSearchResult = false;
    this._searchTimeoutId = null;
    this._containerID = 'itella-' + Math.random().toString(36).substr(2, 6);
    this._modalID = this._containerID + '-modal';

    if (typeof L === 'undefined') {
      console.error(this.strings.error_leaflet);
    }

    itellaRegisterLeafletPlugins();
    this.observeRemoval();
  }

  _createClass(itellaMapping, [{
    key: "observeRemoval",
    value: function observeRemoval() {
      var _this = this;

      new MutationObserver(function (mutations) {
        if (!document.getElementById(_this._containerID)) {
          if (_this._isDebug) {
            console.log('Cleaning up modal ID:', _this._modalID);
          }

          var oldModal = document.getElementById(_this._modalID);

          if (oldModal) {
            oldModal.parentNode.removeChild(oldModal);
          }

          this.disconnect();
        }
      }).observe(document, {
        childList: true,
        subtree: true
      });
    }
  }, {
    key: "setImagesUrl",
    value: function setImagesUrl(images_url) {
      this.images_url = images_url;
      return this;
    }
  }, {
    key: "setStrings",
    value: function setStrings(strings) {
      this.strings = _objectSpread(_objectSpread({}, this.strings), strings);
      return this;
    }
  }, {
    key: "getStrings",
    value: function getStrings() {
      return this.strings;
    }
  }, {
    key: "init",
    value: function init() {
      if (typeof this.mount !== 'undefined') {
        this.buildContainer().buildModal().setupLeafletMap(this.UI.modal.getElementsByClassName('itella-map')[0]).attachListeners();
        return this;
      }

      return false;
    }
  }, {
    key: "getVersion",
    value: function getVersion() {
      return this.version;
    }
  }, {
    key: "getMount",
    value: function getMount() {
      return this.mount;
    }
  }, {
    key: "showEl",
    value: function showEl(el) {
      el.classList.remove('hidden');
    }
  }, {
    key: "hideEl",
    value: function hideEl(el) {
      el.classList.add('hidden');
    }
  }, {
    key: "buildModal",
    value: function buildModal() {
      var template = "\n    <div class=\"itella-container\">\n      <div class=\"close-modal\">\n        <img src=\"".concat(this.images_url, "x-symbol.svg\" alt=\"Close map\">\n      </div>\n      <div class=\"itella-map\"></div>\n      <div class=\"itella-card\">\n        <div class=\"itella-card-header\">\n          <h2>").concat(this.strings.modal_header, "</h2>\n          <img src=\"").concat(this.images_url, "logo_small_white.png\" alt=\"Itella logo\">\n        </div>\n        <div class=\"itella-card-content\">\n          <h3>").concat(this.strings.selector_header, "</h3>\n          <div class=\"itella-select\">\n            <div class=\"dropdown\">").concat(this.strings.select_pickup_point, "</div>\n            <div class=\"dropdown-inner\">\n              <div class=\"search-bar\">\n                <input type=\"text\" placeholder=\"").concat(this.strings.search_placeholder, "\" class=\"search-input\">\n                <img src=\"").concat(this.images_url, "search.png\" alt=\"Search\">\n              </div>\n              <span class=\"search-by\"></span>\n              <ul>\n                <li class=\"city\">").concat(this.strings.no_pickup_points, "</li>\n              </ul>\n            </div>\n          </div>\n\n          <div class=\"point-info\">\n            <div class=\"workhours\">\n              <h4 class=\"title\">").concat(this.strings.workhours_header, "</h4>\n              <div class=\"workhours-info\">\n                <ol>\n                </ol>\n              </div>\n            </div>\n            <div class=\"contacts\">\n              <h4 class=\"title\">").concat(this.strings.contacts_header, "</h4>\n              <div class=\"contacts-info\">\n                <ul>\n                </ul>\n              </div>\n            </div>\n          </div>\n        </div>\n        <div class=\"itella-card-footer\">\n          <button class=\"itella-btn itella-back hidden\">").concat(this.strings.back_to_list_btn, "</button>\n          <button class=\"itella-btn itella-submit\">").concat(this.strings.select_btn, "</button>\n        </div>\n      </div>\n    </div>\n    ");

      if (typeof this.mount === 'undefined') {
        console.info(this.strings.error_missing_mount_el);
        return false;
      }

      var modal = this.createElement('div', ['itella-mapping-modal', 'hidden']);
      modal.innerHTML = template;
      modal.id = this._modalID;
      /* if exists destroy and rebuild */

      if (this.UI.modal !== null) {
        this.UI.modal.parentNode.removeChild(this.UI.modal);
        this.UI.modal = null;
      }

      this.UI.modal = modal;
      document.body.appendChild(this.UI.modal);
      this.UI.back_to_list_btn = this.UI.modal.querySelector('.itella-btn.itella-back');
      return this;
    }
  }, {
    key: "buildContainer",
    value: function buildContainer() {
      var template = "\n      <div class=\"itella-chosen-point\">".concat(this.strings.select_pickup_point, "</div>\n      <a href='#' class=\"itella-modal-btn\">").concat(this.strings.select_pickup_point_btn, "</a>\n    ");
      var container = this.createElement('div', ['itella-shipping-container']);
      container.id = this._containerID;
      container.innerHTML = template;
      this.UI.container = container;
      this.mount.appendChild(this.UI.container);
      return this;
    }
  }, {
    key: "attachListeners",
    value: function attachListeners() {
      var _this = this;

      this.UI.container.getElementsByClassName('itella-modal-btn')[0].addEventListener('click', function (e) {
        e.preventDefault();

        _this.showEl(_this.UI.modal);

        _this._map.invalidateSize();

        if (_this.selectedPoint === null) {
          _this.setMapView(_this._defaultMapPos, _this.ZOOM_DEFAULT);
        } else {
          _this.setMapView(_this.selectedPoint.location, _this.ZOOM_SELECTED);

          if (typeof _this.selectedPoint._marker._icon !== 'undefined' && _this.selectedPoint._marker._icon) {
            _this.selectedPoint._marker._icon.classList.add('active');
          }
        }
      });
      this.UI.modal.getElementsByClassName('search-input')[0].addEventListener('keyup', function (e) {
        e.preventDefault();
        var force = false;
        /* Enter key forces search to not wait */

        if (e.keyCode == '13') {
          force = true;
        }

        _this.searchNearestDebounce(this.value, force);
      });
      this.UI.modal.getElementsByClassName('close-modal')[0].addEventListener('click', function (e) {
        e.preventDefault();

        _this.hideEl(_this.UI.modal);
      });
      this.UI.modal.getElementsByClassName('itella-submit')[0].addEventListener('click', function (e) {
        e.preventDefault();

        _this.submitSelection();
      });

      var select = _this.UI.modal.querySelector('.itella-select');

      var drpd = _this.UI.modal.querySelector('.itella-select .dropdown');

      var select_options = select.querySelector('.dropdown-inner');
      drpd.addEventListener('click', function (e) {
        e.preventDefault();
        select.classList.toggle('open');
      });
      this.UI.modal.addEventListener('click', function (e) {
        if (select.classList.contains('open') && !(select_options.contains(e.target) || drpd == e.target)) {
          select.classList.remove('open');
        }

        if (_this._isDebug) {
          console.log('CLICKED HTML EL:', e.target.nodeName, e.target.dataset);
        }

        if (e.target.nodeName.toLowerCase() == 'li' && typeof e.target.dataset.id !== 'undefined') {
          var point = _this.getLocationById(e.target.dataset.id);

          if (_this._isDebug) {
            console.log('Selected from dropdown:', point);
          }

          _this.selectedPoint = point;

          _this.renderPointInfo(point);

          select.classList.remove('open');

          _this.setMapView(point.location, _this.ZOOM_SELECTED);

          _this.setActiveMarkerByTerminalId(e.target.dataset.id);
        }
      });

      this._markerLayer.on('clusterclick', function (a) {
        // a.layer is actually a cluster
        a.layer.zoomToBounds();
      });

      this._markerLayer.on('click', function (e) {
        _this.removeActiveClass();

        L.DomUtil.addClass(e.layer._icon, "active");

        _this.setMapView(e.layer.getLatLng(), _this._map.getZoom());

        var temp = _this.getLocationById(e.layer.options.pickupPointId);

        _this.renderPointInfo(temp);

        _this.selectedPoint = temp;

        if (_this._isDebug) {
          console.log('Selected pickup point ID:', temp, e.layer.getLatLng());
        }
      });

      this._markerLayer.on('animationend', function (e) {
        if (_this.selectedPoint && _this.selectedPoint._marker._icon) {
          _this.selectedPoint._marker._icon.classList.add('active');
        }
      });

      return this;
    }
  }, {
    key: "removeActiveClass",
    value: function removeActiveClass() {
      if (this.selectedPoint && this.selectedPoint._marker._icon) {
        this.selectedPoint._marker._icon.classList.remove('active');
      }
    }
  }, {
    key: "resetSearch",
    value: function resetSearch() {
      this._isSearchResult = false;
      this.locations.forEach(function (loc) {
        loc.distance = undefined;
      });
      this.locations.sort(this.sortByCity);
      this.updateDropdown();
      this.UI.modal.querySelector('.search-by').innerText = '';
      this.UI.modal.getElementsByClassName('search-input')[0].value = '';
    }
  }, {
    key: "searchNearestDebounce",
    value: function searchNearestDebounce(search, force) {
      var _this = this;

      clearTimeout(this._searchTimeoutId);
      /* if enter is pressed no need to wait */

      if (force) {
        this.searchNearest(search);
        return;
      }

      this._searchTimeoutId = setTimeout(this.searchNearest.bind(_this), 1000, search);
    }
  }, {
    key: "searchNearest",
    value: function searchNearest(search) {
      var _this = this;

      clearTimeout(_this._searchTimeoutId);
      /* reset dropdown if search is empty */

      if (!search.length) {
        this.resetSearch();
      }

      var oReq = new XMLHttpRequest();
      /* access itella class inside response handler */

      oReq.itella = this;
      oReq.addEventListener('loadend', this._handleResponse);
      oReq.open('GET', "https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?singleLine=" + search + "&sourceCountry=" + this.country + "&category=&outFields=Postal&maxLocations=1&forStorage=false&f=pjson");
      oReq.send();
    }
  }, {
    key: "_handleResponse",
    value: function _handleResponse() {
      var _this = this.itella;

      var search_by = _this.UI.modal.querySelector('.search-by');

      if (this.status != 200) {
        search_by.innerText = _this.strings.nothing_found;
        return false;
      }

      var json = JSON.parse(this.responseText);

      if (_this._isDebug) {
        console.log('GEOCODE RESPONSE:', json);
      }

      if (json.candidates != undefined && json.candidates.length > 0) {
        _this._isSearchResult = true;
        search_by.innerText = json.candidates[0].address;

        _this.addDistance({
          lat: json.candidates[0].location.y,
          lon: json.candidates[0].location.x
        });

        return true;
      }

      search_by.innerText = _this.strings.nothing_found;
      return false;
    }
  }, {
    key: "addDistance",
    value: function addDistance(origin) {
      var _this = this;

      this.locations.forEach(function (loc) {
        loc.distance = _this.calculateDistance(origin, loc.location);
      });
      this.locations.sort(this.sortByDistance);
      this.updateDropdown();
    }
  }, {
    key: "deg2rad",
    value: function deg2rad(degress) {
      return degress * Math.PI / 180;
    }
  }, {
    key: "rad2deg",
    value: function rad2deg(radians) {
      return radians * 180 / Math.PI;
    }
  }, {
    key: "calculateDistance",
    value: function calculateDistance(loc1, loc2) {
      var distance = NaN;

      if (loc1.lat == loc2.$lat && loc.lon == loc2.lon) {
        return 0;
      } else {
        var theta = loc1.lon - loc2.lon;
        var dist = Math.sin(this.deg2rad(loc1.lat)) * Math.sin(this.deg2rad(loc2.lat)) + Math.cos(this.deg2rad(loc1.lat)) * Math.cos(this.deg2rad(loc2.lat)) * Math.cos(this.deg2rad(theta));
        dist = Math.acos(dist);
        dist = this.rad2deg(dist);
        distance = dist * 60 * 1.1515 * 1.609344;
      }

      return distance;
    }
  }, {
    key: "registerCallback",
    value: function registerCallback(callback) {
      if (typeof callback !== 'function') {
        return this; // ignore invalid argument
      }

      this.callbackList.push(callback);
      return this;
    }
    /**
     * To work with IE11 we cant use array.find() so this replaces it for our simple usecase
     * @param {Function} checkFn 
     * @param {Array} array 
     */

  }, {
    key: "findLocInArray",
    value: function findLocInArray(checkFn, array) {
      for (var i = 0; i < array.length; i++) {
        if (checkFn(array[i])) {
          return array[i];
        }
      }

      return undefined;
    }
  }, {
    key: "setSelection",
    value: function setSelection(id) {
      var manual = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
      var location = this.getLocationByPupCode(id);

      if (!location) {
        // try looking by ID
        location = this.getLocationById(id);
      }

      if (typeof location !== 'undefined') {
        this.selectedPoint = location;
        this.renderPointInfo(location);
        this.setActiveMarkerByTerminalId(location.id);
        this.setMapView(location.location, this.ZOOM_SELECTED);
        this.submitSelection(manual);
      }
    }
  }, {
    key: "submitSelection",
    value: function submitSelection() {
      var manual = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;

      /* make sure there is something selected */
      if (this.selectedPoint == null) {
        return false;
      }

      var _this = this;

      if (this.callbackList.length > 0) {
        this.callbackList.forEach(function (callback) {
          callback.call(_this, manual);
        });
      }

      var selectedEl = this.UI.container.getElementsByClassName('itella-chosen-point')[0];
      selectedEl.innerText = this.selectedPoint.publicName + ', ' + this.selectedPoint.address.address;
      this.hideEl(this.UI.modal);
    }
  }, {
    key: "renderPointInfo",
    value: function renderPointInfo(location) {
      this.locations.forEach(function (loc) {
        return loc._li.classList.remove('active');
      });

      location._li.classList.add('active');

      var pointInfo = this.UI.modal.querySelector('.point-info');
      var workhours = pointInfo.querySelector('.workhours ol');
      var contacts = pointInfo.querySelector('.contacts ul');
      var openingTimes = [];
      location.openingTimes.forEach(function (time) {
        openingTimes[time.weekday] = {
          from: time.timeFrom,
          to: time.timeTo
        };
      });
      var openHTML = '<div>' + this.strings.no_information + '</div>';

      if (openingTimes.length) {
        openHTML = openingTimes.map(function (time) {
          return "<li>".concat(time.from, " - ").concat(time.to, "</li>");
        }).join('\n');
      }

      workhours.innerHTML = openHTML;
      var contactHTML = '<div>' + this.strings.no_information + '</div>';
      contactHTML = "\n      <li>".concat(location.address.streetName, " ").concat(location.address.streetNumber, ",</li>\n      <li>").concat(location.address.municipality, " ").concat(location.address.postalCode, "</li>\n    ");

      if (location.locationName !== null) {
        contactHTML += "<li>".concat(location.locationName, "</li>");
      }

      if (location.customerServicePhoneNumber !== null) {
        contactHTML += "<li>".concat(location.customerServicePhoneNumber, "</li>");
      }

      if (location.additionalInfo !== null) {
        contactHTML += "<li>".concat(location.additionalInfo, "</li>");
      }

      contacts.innerHTML = contactHTML;
      var drpd = this.UI.modal.querySelector('.itella-select .dropdown');
      drpd.innerText = location.publicName + ', ' + location.address.address;
      return this;
    }
  }, {
    key: "setActiveMarkerByTerminalId",
    value: function setActiveMarkerByTerminalId(id) {
      if (this.selectedPoint && this.selectedPoint._marker._icon) {
        this.selectedPoint._marker._icon.classList.add('active');
      }
    }
  }, {
    key: "getLocationById",
    value: function getLocationById(id) {
      return this.findLocInArray(function (loc) {
        return loc.id == id;
      }, this.locations);
    }
  }, {
    key: "getLocationByPupCode",
    value: function getLocationByPupCode(id) {
      return this.findLocInArray(function (loc) {
        return loc.pupCode == id;
      }, this.locations);
    }
  }, {
    key: "createElement",
    value: function createElement(tag) {
      var classList = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
      var el = document.createElement(tag);

      if (classList.length) {
        classList.forEach(function (elClass) {
          return el.classList.add(elClass);
        });
      }

      return el;
    }
  }, {
    key: "setupLeafletMap",
    value: function setupLeafletMap(rootEl) {
      this._map = L.map(rootEl, {
        zoomControl: false,
        minZoom: this.ZOOM_MIN,
        maxZoom: this.ZOOM_MAX
      }).setActiveArea({
        position: 'absolute',
        top: '0',
        bottom: '0',
        left: '370px',
        // it will be changed dynamicaly
        right: '0'
      });
      new L.Control.Zoom({
        position: 'bottomright'
      }).addTo(this._map);
      var Icon = L.Icon.extend({
        options: {
          iconSize: [29, 34],
          iconAnchor: [15, 34],
          popupAnchor: [-3, -76]
        }
      });
      this._pickupIcon = new Icon({
        iconUrl: this.images_url + 'marker.png'
      });
      L.tileLayer('https://map.plugins.itella.com/tile/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.mijora.lt">Mijora</a>' + ' | Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>'
      }).addTo(this._map);
      this._markerLayer = L.markerClusterGroup({
        zoomToBoundsOnClick: false
      });

      this._map.addLayer(this._markerLayer);

      return this;
    }
  }, {
    key: "setMapView",
    value: function setMapView(targetLatLng, targetZoom) {
      if (window.matchMedia("(min-width: 769px)").matches) {
        var offset = this.getElClientWidth(this.UI.modal.getElementsByClassName('itella-card')[0]);
        this._map._viewport.style.left = offset + 'px';
      } else {
        this._map._viewport.style.left = '0';
      }

      this._map.setView(targetLatLng, targetZoom);
    }
  }, {
    key: "getElClientWidth",
    value: function getElClientWidth(el) {
      return el.clientWidth;
    }
  }, {
    key: "addMarker",
    value: function addMarker(latLong, id) {
      var marker = L.marker(latLong, {
        icon: this._pickupIcon,
        pickupPointId: id
      });

      this._markerLayer.addLayer(marker);

      return marker;
    }
  }, {
    key: "updateMapMarkers",
    value: function updateMapMarkers() {
      var _this = this;

      if (this._markerLayer !== null) {
        this._markerLayer.clearLayers();
      }
      /* add markers to marker layer and link icon in locations list */


      this.locations.forEach(function (location) {
        location._marker = _this.addMarker(location.location, location.id);
      });
      return this;
    }
  }, {
    key: "sortByCity",
    value: function sortByCity(a, b) {
      var result = a.address.municipality.toLowerCase().localeCompare(b.address.municipality.toLowerCase());

      if (result == 0) {
        if (a.type.toUpperCase() == 'SMARTPOST' && b.type.toUpperCase() != 'SMARTPOST') {
          result = -1;
        }

        if (b.type.toUpperCase() == 'SMARTPOST' && a.type.toUpperCase() != 'SMARTPOST') {
          result = 1;
        }
      }

      if (result == 0) {
        result = a.publicName.toLowerCase().localeCompare(b.publicName.toLowerCase());
      }

      return result;
    }
  }, {
    key: "sortByDistance",
    value: function sortByDistance(a, b) {
      return a.distance - b.distance;
    }
  }, {
    key: "setLocations",
    value: function setLocations(locations) {
      var update = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;

      /* clone for inner use */
      this.locations = Array.isArray(locations) ? JSON.parse(JSON.stringify(locations)) : [];
      this.locations.sort(this.sortByCity);
      /* calculate defaultMapPos */

      var _latlongArray = [];
      this.locations.forEach(function (loc) {
        _latlongArray.push(loc.location);
      });

      if (_latlongArray.length > 0) {
        var bounds = new L.LatLngBounds(_latlongArray);
        this._defaultMapPos = bounds.getCenter();
      }

      if (update) {
        this.updateMapMarkers();
        this.updateDropdown();
      }

      return this;
    }
  }, {
    key: "getLocationCount",
    value: function getLocationCount() {
      return this.locations.length;
    }
  }, {
    key: "updateDropdown",
    value: function updateDropdown() {
      var _this = this;
      /**
       * @type HTMLElement
       */


      var dropdown = this.UI.modal.querySelector('.itella-select .dropdown-inner ul');

      if (!this.locations.length) {
        dropdown.innerHTML = '<li class="city">' + this.strings.no_pickup_points + '</li>';
        return this;
      }

      dropdown.innerHTML = '';
      var listHTML = [];
      var city = false;
      this.locations.forEach(function (loc) {
        if (city !== loc.address.municipality.toLowerCase()) {
          city = loc.address.municipality.toLowerCase();

          var cityEl = _this.createElement('li', ['city']);

          cityEl.innerText = loc.address.municipality;
          listHTML.push(cityEl);
        }
        /* check if we allready have html object, otherwise create new one */


        var li = Object.prototype.toString.call(loc._li) == '[object HTMLLIElement]' ? loc._li : _this.createElement('li');
        li.innerHTML = loc.publicName + ', ' + loc.address.address;

        if (typeof loc.distance != 'undefined') {
          var span = _this.createElement('span');

          span.innerText = loc.distance.toFixed(2);
          li.appendChild(span);
        }

        li.dataset.id = loc.id;
        listHTML.push(li);
        loc._li = li;
      });
      var docFrag = document.createDocumentFragment();
      listHTML.forEach(function (el) {
        return docFrag.appendChild(el);
      });
      dropdown.appendChild(docFrag);

      if (this.selectedPoint != null) {
        this.selectedPoint._li.classList.add('active');
      }

      return this;
    }
  }, {
    key: "setCountry",
    value: function setCountry(country_iso2_code) {
      this.country = country_iso2_code;
      return this;
    }
  }, {
    key: "setDebug",
    value: function setDebug() {
      var isOn = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      this._isDebug = isOn;
      return this;
    }
  }]);

  return itellaMapping;
}();

window.itellaRegisterLeafletPlugins = function () {
  /**
   * Leaflet-active-area plugin
   * https://github.com/Mappy/Leaflet-active-area
   * License: Apache 2.0
   * 
   */
  if (typeof L.Map.prototype.setActiveArea == 'undefined') {
    (function (previousMethods) {
      if (typeof previousMethods === 'undefined') {
        // Defining previously that object allows you to use that plugin even if you have overridden L.map
        previousMethods = {
          getCenter: L.Map.prototype.getCenter,
          setView: L.Map.prototype.setView,
          flyTo: L.Map.prototype.flyTo,
          setZoomAround: L.Map.prototype.setZoomAround,
          getBoundsZoom: L.Map.prototype.getBoundsZoom,
          PopupAdjustPan: L.Popup.prototype._adjustPan,
          RendererUpdate: L.Renderer.prototype._update
        };
      }

      L.Map.include({
        getBounds: function getBounds() {
          if (this._viewport) {
            return this.getViewportLatLngBounds();
          } else {
            var bounds = this.getPixelBounds(),
                sw = this.unproject(bounds.getBottomLeft()),
                ne = this.unproject(bounds.getTopRight());
            return new L.LatLngBounds(sw, ne);
          }
        },
        getViewport: function getViewport() {
          return this._viewport;
        },
        getViewportBounds: function getViewportBounds() {
          var vp = this._viewport,
              topleft = L.point(vp.offsetLeft, vp.offsetTop),
              vpsize = L.point(vp.clientWidth, vp.clientHeight);

          if (vpsize.x === 0 || vpsize.y === 0) {
            //Our own viewport has no good size - so we fallback to the container size:
            vp = this.getContainer();

            if (vp) {
              topleft = L.point(0, 0);
              vpsize = L.point(vp.clientWidth, vp.clientHeight);
            }
          }

          return L.bounds(topleft, topleft.add(vpsize));
        },
        getViewportLatLngBounds: function getViewportLatLngBounds() {
          var bounds = this.getViewportBounds();
          return L.latLngBounds(this.containerPointToLatLng(bounds.min), this.containerPointToLatLng(bounds.max));
        },
        getOffset: function getOffset() {
          var mCenter = this.getSize().divideBy(2),
              vCenter = this.getViewportBounds().getCenter();
          return mCenter.subtract(vCenter);
        },
        getCenter: function getCenter(withoutViewport) {
          var center = previousMethods.getCenter.call(this);

          if (this.getViewport() && !withoutViewport) {
            var zoom = this.getZoom(),
                point = this.project(center, zoom);
            point = point.subtract(this.getOffset());
            center = this.unproject(point, zoom);
          }

          return center;
        },
        setView: function setView(center, zoom, options) {
          center = L.latLng(center);
          zoom = zoom === undefined ? this._zoom : this._limitZoom(zoom);

          if (this.getViewport()) {
            var point = this.project(center, this._limitZoom(zoom));
            point = point.add(this.getOffset());
            center = this.unproject(point, this._limitZoom(zoom));
          }

          return previousMethods.setView.call(this, center, zoom, options);
        },
        flyTo: function flyTo(targetCenter, targetZoom, options) {
          targetCenter = L.latLng(targetCenter);
          targetZoom = targetZoom === undefined ? startZoom : targetZoom;

          if (this.getViewport()) {
            var point = this.project(targetCenter, this._limitZoom(targetZoom));
            point = point.add(this.getOffset());
            targetCenter = this.unproject(point, this._limitZoom(targetZoom));
          }

          options = options || {};

          if (options.animate === false || !L.Browser.any3d) {
            return this.setView(targetCenter, targetZoom, options);
          }

          this._stop();

          var from = this.project(previousMethods.getCenter.call(this)),
              to = this.project(targetCenter),
              size = this.getSize(),
              startZoom = this._zoom;
          var w0 = Math.max(size.x, size.y),
              w1 = w0 * this.getZoomScale(startZoom, targetZoom),
              u1 = to.distanceTo(from) || 1,
              rho = 1.42,
              rho2 = rho * rho;

          function r(i) {
            var s1 = i ? -1 : 1,
                s2 = i ? w1 : w0,
                t1 = w1 * w1 - w0 * w0 + s1 * rho2 * rho2 * u1 * u1,
                b1 = 2 * s2 * rho2 * u1,
                b = t1 / b1,
                sq = Math.sqrt(b * b + 1) - b; // workaround for floating point precision bug when sq = 0, log = -Infinite,
            // thus triggering an infinite loop in flyTo

            var log = sq < 0.000000001 ? -18 : Math.log(sq);
            return log;
          }

          function sinh(n) {
            return (Math.exp(n) - Math.exp(-n)) / 2;
          }

          function cosh(n) {
            return (Math.exp(n) + Math.exp(-n)) / 2;
          }

          function tanh(n) {
            return sinh(n) / cosh(n);
          }

          var r0 = r(0);

          function w(s) {
            return w0 * (cosh(r0) / cosh(r0 + rho * s));
          }

          function u(s) {
            return w0 * (cosh(r0) * tanh(r0 + rho * s) - sinh(r0)) / rho2;
          }

          function easeOut(t) {
            return 1 - Math.pow(1 - t, 1.5);
          }

          var start = Date.now(),
              S = (r(1) - r0) / rho,
              duration = options.duration ? 1000 * options.duration : 1000 * S * 0.8;

          function frame() {
            var t = (Date.now() - start) / duration,
                s = easeOut(t) * S;

            if (t <= 1) {
              this._flyToFrame = L.Util.requestAnimFrame(frame, this);

              this._move(this.unproject(from.add(to.subtract(from).multiplyBy(u(s) / u1)), startZoom), this.getScaleZoom(w0 / w(s), startZoom), {
                flyTo: true
              });
            } else {
              this._move(targetCenter, targetZoom)._moveEnd(true);
            }
          }

          this._moveStart(true, options.noMoveStart);

          frame.call(this);
          return this;
        },
        setZoomAround: function setZoomAround(latlng, zoom, options) {
          var vp = this.getViewport();

          if (vp) {
            var scale = this.getZoomScale(zoom),
                viewHalf = this.getViewportBounds().getCenter(),
                containerPoint = latlng instanceof L.Point ? latlng : this.latLngToContainerPoint(latlng),
                centerOffset = containerPoint.subtract(viewHalf).multiplyBy(1 - 1 / scale),
                newCenter = this.containerPointToLatLng(viewHalf.add(centerOffset));
            return this.setView(newCenter, zoom, {
              zoom: options
            });
          } else {
            return previousMethods.setZoomAround.call(this, latlng, zoom, options);
          }
        },
        getBoundsZoom: function getBoundsZoom(bounds, inside, padding) {
          // (LatLngBounds[, Boolean, Point]) -> Number
          bounds = L.latLngBounds(bounds);
          padding = L.point(padding || [0, 0]);
          var zoom = this.getZoom() || 0,
              min = this.getMinZoom(),
              max = this.getMaxZoom(),
              nw = bounds.getNorthWest(),
              se = bounds.getSouthEast(),
              vp = this.getViewport(),
              size = (vp ? L.point(vp.clientWidth, vp.clientHeight) : this.getSize()).subtract(padding),
              boundsSize = this.project(se, zoom).subtract(this.project(nw, zoom)),
              snap = L.Browser.any3d ? this.options.zoomSnap : 1;
          var scale = Math.min(size.x / boundsSize.x, size.y / boundsSize.y);
          zoom = this.getScaleZoom(scale, zoom);

          if (snap) {
            zoom = Math.round(zoom / (snap / 100)) * (snap / 100); // don't jump if within 1% of a snap level

            zoom = inside ? Math.ceil(zoom / snap) * snap : Math.floor(zoom / snap) * snap;
          }

          return Math.max(min, Math.min(max, zoom));
        }
      });
      L.Map.include({
        setActiveArea: function setActiveArea(css, keepCenter, animate) {
          var center;

          if (keepCenter && this._zoom) {
            // save center if map is already initialized
            // and keepCenter is passed
            center = this.getCenter();
          }

          if (!this._viewport) {
            //Make viewport if not already made
            var container = this.getContainer();
            this._viewport = L.DomUtil.create('div', '');
            container.insertBefore(this._viewport, container.firstChild);
          }

          if (typeof css === 'string') {
            this._viewport.className = css;
          } else {
            L.extend(this._viewport.style, css);
          }

          if (center) {
            this.setView(center, this.getZoom(), {
              animate: !!animate
            });
          }

          return this;
        }
      });
      L.Renderer.include({
        _onZoom: function _onZoom() {
          this._updateTransform(this._map.getCenter(true), this._map.getZoom());
        },
        _update: function _update() {
          previousMethods.RendererUpdate.call(this);
          this._center = this._map.getCenter(true);
        }
      });
      L.GridLayer.include({
        _updateLevels: function _updateLevels() {
          var zoom = this._tileZoom,
              maxZoom = this.options.maxZoom;

          if (zoom === undefined) {
            return undefined;
          }

          for (var z in this._levels) {
            if (this._levels[z].el.children.length || z === zoom) {
              this._levels[z].el.style.zIndex = maxZoom - Math.abs(zoom - z);
            } else {
              L.DomUtil.remove(this._levels[z].el);

              this._removeTilesAtZoom(z);

              delete this._levels[z];
            }
          }

          var level = this._levels[zoom],
              map = this._map;

          if (!level) {
            level = this._levels[zoom] = {};
            level.el = L.DomUtil.create('div', 'leaflet-tile-container leaflet-zoom-animated', this._container);
            level.el.style.zIndex = maxZoom;
            level.origin = map.project(map.unproject(map.getPixelOrigin()), zoom).round();
            level.zoom = zoom;

            this._setZoomTransform(level, map.getCenter(true), map.getZoom()); // force the browser to consider the newly added element for transition


            L.Util.falseFn(level.el.offsetWidth);
          }

          this._level = level;
          return level;
        },
        _resetView: function _resetView(e) {
          var animating = e && (e.pinch || e.flyTo);

          this._setView(this._map.getCenter(true), this._map.getZoom(), animating, animating);
        },
        _update: function _update(center) {
          var map = this._map;

          if (!map) {
            return;
          }

          var zoom = this._clampZoom(map.getZoom());

          if (center === undefined) {
            center = map.getCenter(this);
          }

          if (this._tileZoom === undefined) {
            return;
          } // if out of minzoom/maxzoom


          var pixelBounds = this._getTiledPixelBounds(center),
              tileRange = this._pxBoundsToTileRange(pixelBounds),
              tileCenter = tileRange.getCenter(),
              queue = [];

          for (var key in this._tiles) {
            this._tiles[key].current = false;
          } // _update just loads more tiles. If the tile zoom level differs too much
          // from the map's, let _setView reset levels and prune old tiles.


          if (Math.abs(zoom - this._tileZoom) > 1) {
            this._setView(center, zoom);

            return;
          } // create a queue of coordinates to load tiles from


          for (var j = tileRange.min.y; j <= tileRange.max.y; j++) {
            for (var i = tileRange.min.x; i <= tileRange.max.x; i++) {
              var coords = new L.Point(i, j);
              coords.z = this._tileZoom;

              if (!this._isValidTile(coords)) {
                continue;
              }

              var tile = this._tiles[this._tileCoordsToKey(coords)];

              if (tile) {
                tile.current = true;
              } else {
                queue.push(coords);
              }
            }
          } // sort tile queue to load tiles in order of their distance to center


          queue.sort(function (a, b) {
            return a.distanceTo(tileCenter) - b.distanceTo(tileCenter);
          });

          if (queue.length !== 0) {
            // if its the first batch of tiles to load
            if (!this._loading) {
              this._loading = true; // @event loading: Event
              // Fired when the grid layer starts loading tiles

              this.fire('loading');
            } // create DOM fragment to append tiles in one batch


            var fragment = document.createDocumentFragment();

            for (i = 0; i < queue.length; i++) {
              this._addTile(queue[i], fragment);
            }

            this._level.el.appendChild(fragment);
          }
        }
      });
      L.Popup.include({
        _adjustPan: function _adjustPan() {
          if (!this._map._viewport) {
            previousMethods.PopupAdjustPan.call(this);
          } else {
            if (!this.options.autoPan || this._map._panAnim && this._map._panAnim._inProgress) {
              return;
            }

            var map = this._map,
                vp = map._viewport,
                containerHeight = this._container.offsetHeight,
                containerWidth = this._containerWidth,
                vpTopleft = L.point(vp.offsetLeft, vp.offsetTop),
                layerPos = new L.Point(this._containerLeft - vpTopleft.x, -containerHeight - this._containerBottom - vpTopleft.y);

            if (this._zoomAnimated) {
              layerPos._add(L.DomUtil.getPosition(this._container));
            }

            var containerPos = map.layerPointToContainerPoint(layerPos),
                padding = L.point(this.options.autoPanPadding),
                paddingTL = L.point(this.options.autoPanPaddingTopLeft || padding),
                paddingBR = L.point(this.options.autoPanPaddingBottomRight || padding),
                size = L.point(vp.clientWidth, vp.clientHeight),
                dx = 0,
                dy = 0;

            if (containerPos.x + containerWidth + paddingBR.x > size.x) {
              // right
              dx = containerPos.x + containerWidth - size.x + paddingBR.x;
            }

            if (containerPos.x - dx - paddingTL.x < 0) {
              // left
              dx = containerPos.x - paddingTL.x;
            }

            if (containerPos.y + containerHeight + paddingBR.y > size.y) {
              // bottom
              dy = containerPos.y + containerHeight - size.y + paddingBR.y;
            }

            if (containerPos.y - dy - paddingTL.y < 0) {
              // top
              dy = containerPos.y - paddingTL.y;
            } // @namespace Map
            // @section Popup events
            // @event autopanstart
            // Fired when the map starts autopanning when opening a popup.


            if (dx || dy) {
              map.fire('autopanstart').panBy([dx, dy]);
            }
          }
        }
      });
    })(window.leafletActiveAreaPreviousMethods);
  }
  /**
   * Leaflet.markercluster plugin
   * https://github.com/Leaflet/Leaflet.markercluster
   * License: MIT
   * 
   */


  if (typeof L.markerClusterGroup == 'undefined') {
    window.Leaflet = window.Leaflet || {};
    window.Leaflet.markercluster = window.Leaflet.markercluster || {};
    !function (e) {
      "use strict";

      var t = L.MarkerClusterGroup = L.FeatureGroup.extend({
        options: {
          maxClusterRadius: 80,
          iconCreateFunction: null,
          clusterPane: L.Marker.prototype.options.pane,
          spiderfyOnMaxZoom: !0,
          showCoverageOnHover: !0,
          zoomToBoundsOnClick: !0,
          singleMarkerMode: !1,
          disableClusteringAtZoom: null,
          removeOutsideVisibleBounds: !0,
          animate: !0,
          animateAddingMarkers: !1,
          spiderfyDistanceMultiplier: 1,
          spiderLegPolylineOptions: {
            weight: 1.5,
            color: "#222",
            opacity: .5
          },
          chunkedLoading: !1,
          chunkInterval: 200,
          chunkDelay: 50,
          chunkProgress: null,
          polygonOptions: {}
        },
        initialize: function initialize(e) {
          L.Util.setOptions(this, e), this.options.iconCreateFunction || (this.options.iconCreateFunction = this._defaultIconCreateFunction), this._featureGroup = L.featureGroup(), this._featureGroup.addEventParent(this), this._nonPointGroup = L.featureGroup(), this._nonPointGroup.addEventParent(this), this._inZoomAnimation = 0, this._needsClustering = [], this._needsRemoving = [], this._currentShownBounds = null, this._queue = [], this._childMarkerEventHandlers = {
            dragstart: this._childMarkerDragStart,
            move: this._childMarkerMoved,
            dragend: this._childMarkerDragEnd
          };
          var t = L.DomUtil.TRANSITION && this.options.animate;
          L.extend(this, t ? this._withAnimation : this._noAnimation), this._markerCluster = t ? L.MarkerCluster : L.MarkerClusterNonAnimated;
        },
        addLayer: function addLayer(e) {
          if (e instanceof L.LayerGroup) return this.addLayers([e]);
          if (!e.getLatLng) return this._nonPointGroup.addLayer(e), this.fire("layeradd", {
            layer: e
          }), this;
          if (!this._map) return this._needsClustering.push(e), this.fire("layeradd", {
            layer: e
          }), this;
          if (this.hasLayer(e)) return this;
          this._unspiderfy && this._unspiderfy(), this._addLayer(e, this._maxZoom), this.fire("layeradd", {
            layer: e
          }), this._topClusterLevel._recalculateBounds(), this._refreshClustersIcons();
          var t = e,
              i = this._zoom;
          if (e.__parent) for (; t.__parent._zoom >= i;) {
            t = t.__parent;
          }
          return this._currentShownBounds.contains(t.getLatLng()) && (this.options.animateAddingMarkers ? this._animationAddLayer(e, t) : this._animationAddLayerNonAnimated(e, t)), this;
        },
        removeLayer: function removeLayer(e) {
          return e instanceof L.LayerGroup ? this.removeLayers([e]) : e.getLatLng ? this._map ? e.__parent ? (this._unspiderfy && (this._unspiderfy(), this._unspiderfyLayer(e)), this._removeLayer(e, !0), this.fire("layerremove", {
            layer: e
          }), this._topClusterLevel._recalculateBounds(), this._refreshClustersIcons(), e.off(this._childMarkerEventHandlers, this), this._featureGroup.hasLayer(e) && (this._featureGroup.removeLayer(e), e.clusterShow && e.clusterShow()), this) : this : (!this._arraySplice(this._needsClustering, e) && this.hasLayer(e) && this._needsRemoving.push({
            layer: e,
            latlng: e._latlng
          }), this.fire("layerremove", {
            layer: e
          }), this) : (this._nonPointGroup.removeLayer(e), this.fire("layerremove", {
            layer: e
          }), this);
        },
        addLayers: function addLayers(e, t) {
          if (!L.Util.isArray(e)) return this.addLayer(e);
          var i,
              n = this._featureGroup,
              r = this._nonPointGroup,
              s = this.options.chunkedLoading,
              o = this.options.chunkInterval,
              a = this.options.chunkProgress,
              h = e.length,
              l = 0,
              u = !0;

          if (this._map) {
            var _ = new Date().getTime(),
                d = L.bind(function () {
              for (var c = new Date().getTime(); h > l; l++) {
                if (s && 0 === l % 200) {
                  var p = new Date().getTime() - c;
                  if (p > o) break;
                }

                if (i = e[l], i instanceof L.LayerGroup) u && (e = e.slice(), u = !1), this._extractNonGroupLayers(i, e), h = e.length;else if (i.getLatLng) {
                  if (!this.hasLayer(i) && (this._addLayer(i, this._maxZoom), t || this.fire("layeradd", {
                    layer: i
                  }), i.__parent && 2 === i.__parent.getChildCount())) {
                    var f = i.__parent.getAllChildMarkers(),
                        m = f[0] === i ? f[1] : f[0];

                    n.removeLayer(m);
                  }
                } else r.addLayer(i), t || this.fire("layeradd", {
                  layer: i
                });
              }

              a && a(l, h, new Date().getTime() - _), l === h ? (this._topClusterLevel._recalculateBounds(), this._refreshClustersIcons(), this._topClusterLevel._recursivelyAddChildrenToMap(null, this._zoom, this._currentShownBounds)) : setTimeout(d, this.options.chunkDelay);
            }, this);

            d();
          } else for (var c = this._needsClustering; h > l; l++) {
            i = e[l], i instanceof L.LayerGroup ? (u && (e = e.slice(), u = !1), this._extractNonGroupLayers(i, e), h = e.length) : i.getLatLng ? this.hasLayer(i) || c.push(i) : r.addLayer(i);
          }

          return this;
        },
        removeLayers: function removeLayers(e) {
          var t,
              i,
              n = e.length,
              r = this._featureGroup,
              s = this._nonPointGroup,
              o = !0;

          if (!this._map) {
            for (t = 0; n > t; t++) {
              i = e[t], i instanceof L.LayerGroup ? (o && (e = e.slice(), o = !1), this._extractNonGroupLayers(i, e), n = e.length) : (this._arraySplice(this._needsClustering, i), s.removeLayer(i), this.hasLayer(i) && this._needsRemoving.push({
                layer: i,
                latlng: i._latlng
              }), this.fire("layerremove", {
                layer: i
              }));
            }

            return this;
          }

          if (this._unspiderfy) {
            this._unspiderfy();

            var a = e.slice(),
                h = n;

            for (t = 0; h > t; t++) {
              i = a[t], i instanceof L.LayerGroup ? (this._extractNonGroupLayers(i, a), h = a.length) : this._unspiderfyLayer(i);
            }
          }

          for (t = 0; n > t; t++) {
            i = e[t], i instanceof L.LayerGroup ? (o && (e = e.slice(), o = !1), this._extractNonGroupLayers(i, e), n = e.length) : i.__parent ? (this._removeLayer(i, !0, !0), this.fire("layerremove", {
              layer: i
            }), r.hasLayer(i) && (r.removeLayer(i), i.clusterShow && i.clusterShow())) : (s.removeLayer(i), this.fire("layerremove", {
              layer: i
            }));
          }

          return this._topClusterLevel._recalculateBounds(), this._refreshClustersIcons(), this._topClusterLevel._recursivelyAddChildrenToMap(null, this._zoom, this._currentShownBounds), this;
        },
        clearLayers: function clearLayers() {
          return this._map || (this._needsClustering = [], this._needsRemoving = [], delete this._gridClusters, delete this._gridUnclustered), this._noanimationUnspiderfy && this._noanimationUnspiderfy(), this._featureGroup.clearLayers(), this._nonPointGroup.clearLayers(), this.eachLayer(function (e) {
            e.off(this._childMarkerEventHandlers, this), delete e.__parent;
          }, this), this._map && this._generateInitialClusters(), this;
        },
        getBounds: function getBounds() {
          var e = new L.LatLngBounds();
          this._topClusterLevel && e.extend(this._topClusterLevel._bounds);

          for (var t = this._needsClustering.length - 1; t >= 0; t--) {
            e.extend(this._needsClustering[t].getLatLng());
          }

          return e.extend(this._nonPointGroup.getBounds()), e;
        },
        eachLayer: function eachLayer(e, t) {
          var i,
              n,
              r,
              s = this._needsClustering.slice(),
              o = this._needsRemoving;

          for (this._topClusterLevel && this._topClusterLevel.getAllChildMarkers(s), n = s.length - 1; n >= 0; n--) {
            for (i = !0, r = o.length - 1; r >= 0; r--) {
              if (o[r].layer === s[n]) {
                i = !1;
                break;
              }
            }

            i && e.call(t, s[n]);
          }

          this._nonPointGroup.eachLayer(e, t);
        },
        getLayers: function getLayers() {
          var e = [];
          return this.eachLayer(function (t) {
            e.push(t);
          }), e;
        },
        getLayer: function getLayer(e) {
          var t = null;
          return e = parseInt(e, 10), this.eachLayer(function (i) {
            L.stamp(i) === e && (t = i);
          }), t;
        },
        hasLayer: function hasLayer(e) {
          if (!e) return !1;
          var t,
              i = this._needsClustering;

          for (t = i.length - 1; t >= 0; t--) {
            if (i[t] === e) return !0;
          }

          for (i = this._needsRemoving, t = i.length - 1; t >= 0; t--) {
            if (i[t].layer === e) return !1;
          }

          return !(!e.__parent || e.__parent._group !== this) || this._nonPointGroup.hasLayer(e);
        },
        zoomToShowLayer: function zoomToShowLayer(e, t) {
          "function" != typeof t && (t = function t() {});

          var i = function i() {
            !e._icon && !e.__parent._icon || this._inZoomAnimation || (this._map.off("moveend", i, this), this.off("animationend", i, this), e._icon ? t() : e.__parent._icon && (this.once("spiderfied", t, this), e.__parent.spiderfy()));
          };

          e._icon && this._map.getBounds().contains(e.getLatLng()) ? t() : e.__parent._zoom < Math.round(this._map._zoom) ? (this._map.on("moveend", i, this), this._map.panTo(e.getLatLng())) : (this._map.on("moveend", i, this), this.on("animationend", i, this), e.__parent.zoomToBounds());
        },
        onAdd: function onAdd(e) {
          this._map = e;
          var t, i, n;
          if (!isFinite(this._map.getMaxZoom())) throw "Map has no maxZoom specified";

          for (this._featureGroup.addTo(e), this._nonPointGroup.addTo(e), this._gridClusters || this._generateInitialClusters(), this._maxLat = e.options.crs.projection.MAX_LATITUDE, t = 0, i = this._needsRemoving.length; i > t; t++) {
            n = this._needsRemoving[t], n.newlatlng = n.layer._latlng, n.layer._latlng = n.latlng;
          }

          for (t = 0, i = this._needsRemoving.length; i > t; t++) {
            n = this._needsRemoving[t], this._removeLayer(n.layer, !0), n.layer._latlng = n.newlatlng;
          }

          this._needsRemoving = [], this._zoom = Math.round(this._map._zoom), this._currentShownBounds = this._getExpandedVisibleBounds(), this._map.on("zoomend", this._zoomEnd, this), this._map.on("moveend", this._moveEnd, this), this._spiderfierOnAdd && this._spiderfierOnAdd(), this._bindEvents(), i = this._needsClustering, this._needsClustering = [], this.addLayers(i, !0);
        },
        onRemove: function onRemove(e) {
          e.off("zoomend", this._zoomEnd, this), e.off("moveend", this._moveEnd, this), this._unbindEvents(), this._map._mapPane.className = this._map._mapPane.className.replace(" leaflet-cluster-anim", ""), this._spiderfierOnRemove && this._spiderfierOnRemove(), delete this._maxLat, this._hideCoverage(), this._featureGroup.remove(), this._nonPointGroup.remove(), this._featureGroup.clearLayers(), this._map = null;
        },
        getVisibleParent: function getVisibleParent(e) {
          for (var t = e; t && !t._icon;) {
            t = t.__parent;
          }

          return t || null;
        },
        _arraySplice: function _arraySplice(e, t) {
          for (var i = e.length - 1; i >= 0; i--) {
            if (e[i] === t) return e.splice(i, 1), !0;
          }
        },
        _removeFromGridUnclustered: function _removeFromGridUnclustered(e, t) {
          for (var i = this._map, n = this._gridUnclustered, r = Math.floor(this._map.getMinZoom()); t >= r && n[t].removeObject(e, i.project(e.getLatLng(), t)); t--) {
            ;
          }
        },
        _childMarkerDragStart: function _childMarkerDragStart(e) {
          e.target.__dragStart = e.target._latlng;
        },
        _childMarkerMoved: function _childMarkerMoved(e) {
          if (!this._ignoreMove && !e.target.__dragStart) {
            var t = e.target._popup && e.target._popup.isOpen();

            this._moveChild(e.target, e.oldLatLng, e.latlng), t && e.target.openPopup();
          }
        },
        _moveChild: function _moveChild(e, t, i) {
          e._latlng = t, this.removeLayer(e), e._latlng = i, this.addLayer(e);
        },
        _childMarkerDragEnd: function _childMarkerDragEnd(e) {
          var t = e.target.__dragStart;
          delete e.target.__dragStart, t && this._moveChild(e.target, t, e.target._latlng);
        },
        _removeLayer: function _removeLayer(e, t, i) {
          var n = this._gridClusters,
              r = this._gridUnclustered,
              s = this._featureGroup,
              o = this._map,
              a = Math.floor(this._map.getMinZoom());
          t && this._removeFromGridUnclustered(e, this._maxZoom);
          var h,
              l = e.__parent,
              u = l._markers;

          for (this._arraySplice(u, e); l && (l._childCount--, l._boundsNeedUpdate = !0, !(l._zoom < a));) {
            t && l._childCount <= 1 ? (h = l._markers[0] === e ? l._markers[1] : l._markers[0], n[l._zoom].removeObject(l, o.project(l._cLatLng, l._zoom)), r[l._zoom].addObject(h, o.project(h.getLatLng(), l._zoom)), this._arraySplice(l.__parent._childClusters, l), l.__parent._markers.push(h), h.__parent = l.__parent, l._icon && (s.removeLayer(l), i || s.addLayer(h))) : l._iconNeedsUpdate = !0, l = l.__parent;
          }

          delete e.__parent;
        },
        _isOrIsParent: function _isOrIsParent(e, t) {
          for (; t;) {
            if (e === t) return !0;
            t = t.parentNode;
          }

          return !1;
        },
        fire: function fire(e, t, i) {
          if (t && t.layer instanceof L.MarkerCluster) {
            if (t.originalEvent && this._isOrIsParent(t.layer._icon, t.originalEvent.relatedTarget)) return;
            e = "cluster" + e;
          }

          L.FeatureGroup.prototype.fire.call(this, e, t, i);
        },
        listens: function listens(e, t) {
          return L.FeatureGroup.prototype.listens.call(this, e, t) || L.FeatureGroup.prototype.listens.call(this, "cluster" + e, t);
        },
        _defaultIconCreateFunction: function _defaultIconCreateFunction(e) {
          var t = e.getChildCount(),
              i = " marker-cluster-";
          return i += 10 > t ? "small" : 100 > t ? "medium" : "large", new L.DivIcon({
            html: "<div><span>" + t + "</span></div>",
            className: "marker-cluster" + i,
            iconSize: new L.Point(40, 40)
          });
        },
        _bindEvents: function _bindEvents() {
          var e = this._map,
              t = this.options.spiderfyOnMaxZoom,
              i = this.options.showCoverageOnHover,
              n = this.options.zoomToBoundsOnClick;
          (t || n) && this.on("clusterclick", this._zoomOrSpiderfy, this), i && (this.on("clustermouseover", this._showCoverage, this), this.on("clustermouseout", this._hideCoverage, this), e.on("zoomend", this._hideCoverage, this));
        },
        _zoomOrSpiderfy: function _zoomOrSpiderfy(e) {
          for (var t = e.layer, i = t; 1 === i._childClusters.length;) {
            i = i._childClusters[0];
          }

          i._zoom === this._maxZoom && i._childCount === t._childCount && this.options.spiderfyOnMaxZoom ? t.spiderfy() : this.options.zoomToBoundsOnClick && t.zoomToBounds(), e.originalEvent && 13 === e.originalEvent.keyCode && this._map._container.focus();
        },
        _showCoverage: function _showCoverage(e) {
          var t = this._map;
          this._inZoomAnimation || (this._shownPolygon && t.removeLayer(this._shownPolygon), e.layer.getChildCount() > 2 && e.layer !== this._spiderfied && (this._shownPolygon = new L.Polygon(e.layer.getConvexHull(), this.options.polygonOptions), t.addLayer(this._shownPolygon)));
        },
        _hideCoverage: function _hideCoverage() {
          this._shownPolygon && (this._map.removeLayer(this._shownPolygon), this._shownPolygon = null);
        },
        _unbindEvents: function _unbindEvents() {
          var e = this.options.spiderfyOnMaxZoom,
              t = this.options.showCoverageOnHover,
              i = this.options.zoomToBoundsOnClick,
              n = this._map;
          (e || i) && this.off("clusterclick", this._zoomOrSpiderfy, this), t && (this.off("clustermouseover", this._showCoverage, this), this.off("clustermouseout", this._hideCoverage, this), n.off("zoomend", this._hideCoverage, this));
        },
        _zoomEnd: function _zoomEnd() {
          this._map && (this._mergeSplitClusters(), this._zoom = Math.round(this._map._zoom), this._currentShownBounds = this._getExpandedVisibleBounds());
        },
        _moveEnd: function _moveEnd() {
          if (!this._inZoomAnimation) {
            var e = this._getExpandedVisibleBounds();

            this._topClusterLevel._recursivelyRemoveChildrenFromMap(this._currentShownBounds, Math.floor(this._map.getMinZoom()), this._zoom, e), this._topClusterLevel._recursivelyAddChildrenToMap(null, Math.round(this._map._zoom), e), this._currentShownBounds = e;
          }
        },
        _generateInitialClusters: function _generateInitialClusters() {
          var e = Math.ceil(this._map.getMaxZoom()),
              t = Math.floor(this._map.getMinZoom()),
              i = this.options.maxClusterRadius,
              n = i;
          "function" != typeof i && (n = function n() {
            return i;
          }), null !== this.options.disableClusteringAtZoom && (e = this.options.disableClusteringAtZoom - 1), this._maxZoom = e, this._gridClusters = {}, this._gridUnclustered = {};

          for (var r = e; r >= t; r--) {
            this._gridClusters[r] = new L.DistanceGrid(n(r)), this._gridUnclustered[r] = new L.DistanceGrid(n(r));
          }

          this._topClusterLevel = new this._markerCluster(this, t - 1);
        },
        _addLayer: function _addLayer(e, t) {
          var i,
              n,
              r = this._gridClusters,
              s = this._gridUnclustered,
              o = Math.floor(this._map.getMinZoom());

          for (this.options.singleMarkerMode && this._overrideMarkerIcon(e), e.on(this._childMarkerEventHandlers, this); t >= o; t--) {
            i = this._map.project(e.getLatLng(), t);
            var a = r[t].getNearObject(i);
            if (a) return a._addChild(e), e.__parent = a, void 0;

            if (a = s[t].getNearObject(i)) {
              var h = a.__parent;
              h && this._removeLayer(a, !1);
              var l = new this._markerCluster(this, t, a, e);
              r[t].addObject(l, this._map.project(l._cLatLng, t)), a.__parent = l, e.__parent = l;
              var u = l;

              for (n = t - 1; n > h._zoom; n--) {
                u = new this._markerCluster(this, n, u), r[n].addObject(u, this._map.project(a.getLatLng(), n));
              }

              return h._addChild(u), this._removeFromGridUnclustered(a, t), void 0;
            }

            s[t].addObject(e, i);
          }

          this._topClusterLevel._addChild(e), e.__parent = this._topClusterLevel;
        },
        _refreshClustersIcons: function _refreshClustersIcons() {
          this._featureGroup.eachLayer(function (e) {
            e instanceof L.MarkerCluster && e._iconNeedsUpdate && e._updateIcon();
          });
        },
        _enqueue: function _enqueue(e) {
          this._queue.push(e), this._queueTimeout || (this._queueTimeout = setTimeout(L.bind(this._processQueue, this), 300));
        },
        _processQueue: function _processQueue() {
          for (var e = 0; e < this._queue.length; e++) {
            this._queue[e].call(this);
          }

          this._queue.length = 0, clearTimeout(this._queueTimeout), this._queueTimeout = null;
        },
        _mergeSplitClusters: function _mergeSplitClusters() {
          var e = Math.round(this._map._zoom);
          this._processQueue(), this._zoom < e && this._currentShownBounds.intersects(this._getExpandedVisibleBounds()) ? (this._animationStart(), this._topClusterLevel._recursivelyRemoveChildrenFromMap(this._currentShownBounds, Math.floor(this._map.getMinZoom()), this._zoom, this._getExpandedVisibleBounds()), this._animationZoomIn(this._zoom, e)) : this._zoom > e ? (this._animationStart(), this._animationZoomOut(this._zoom, e)) : this._moveEnd();
        },
        _getExpandedVisibleBounds: function _getExpandedVisibleBounds() {
          return this.options.removeOutsideVisibleBounds ? L.Browser.mobile ? this._checkBoundsMaxLat(this._map.getBounds()) : this._checkBoundsMaxLat(this._map.getBounds().pad(1)) : this._mapBoundsInfinite;
        },
        _checkBoundsMaxLat: function _checkBoundsMaxLat(e) {
          var t = this._maxLat;
          return void 0 !== t && (e.getNorth() >= t && (e._northEast.lat = 1 / 0), e.getSouth() <= -t && (e._southWest.lat = -1 / 0)), e;
        },
        _animationAddLayerNonAnimated: function _animationAddLayerNonAnimated(e, t) {
          if (t === e) this._featureGroup.addLayer(e);else if (2 === t._childCount) {
            t._addToMap();

            var i = t.getAllChildMarkers();
            this._featureGroup.removeLayer(i[0]), this._featureGroup.removeLayer(i[1]);
          } else t._updateIcon();
        },
        _extractNonGroupLayers: function _extractNonGroupLayers(e, t) {
          var i,
              n = e.getLayers(),
              r = 0;

          for (t = t || []; r < n.length; r++) {
            i = n[r], i instanceof L.LayerGroup ? this._extractNonGroupLayers(i, t) : t.push(i);
          }

          return t;
        },
        _overrideMarkerIcon: function _overrideMarkerIcon(e) {
          var t = e.options.icon = this.options.iconCreateFunction({
            getChildCount: function getChildCount() {
              return 1;
            },
            getAllChildMarkers: function getAllChildMarkers() {
              return [e];
            }
          });
          return t;
        }
      });
      L.MarkerClusterGroup.include({
        _mapBoundsInfinite: new L.LatLngBounds(new L.LatLng(-1 / 0, -1 / 0), new L.LatLng(1 / 0, 1 / 0))
      }), L.MarkerClusterGroup.include({
        _noAnimation: {
          _animationStart: function _animationStart() {},
          _animationZoomIn: function _animationZoomIn(e, t) {
            this._topClusterLevel._recursivelyRemoveChildrenFromMap(this._currentShownBounds, Math.floor(this._map.getMinZoom()), e), this._topClusterLevel._recursivelyAddChildrenToMap(null, t, this._getExpandedVisibleBounds()), this.fire("animationend");
          },
          _animationZoomOut: function _animationZoomOut(e, t) {
            this._topClusterLevel._recursivelyRemoveChildrenFromMap(this._currentShownBounds, Math.floor(this._map.getMinZoom()), e), this._topClusterLevel._recursivelyAddChildrenToMap(null, t, this._getExpandedVisibleBounds()), this.fire("animationend");
          },
          _animationAddLayer: function _animationAddLayer(e, t) {
            this._animationAddLayerNonAnimated(e, t);
          }
        },
        _withAnimation: {
          _animationStart: function _animationStart() {
            this._map._mapPane.className += " leaflet-cluster-anim", this._inZoomAnimation++;
          },
          _animationZoomIn: function _animationZoomIn(e, t) {
            var i,
                n = this._getExpandedVisibleBounds(),
                r = this._featureGroup,
                s = Math.floor(this._map.getMinZoom());

            this._ignoreMove = !0, this._topClusterLevel._recursively(n, e, s, function (s) {
              var o,
                  a = s._latlng,
                  h = s._markers;

              for (n.contains(a) || (a = null), s._isSingleParent() && e + 1 === t ? (r.removeLayer(s), s._recursivelyAddChildrenToMap(null, t, n)) : (s.clusterHide(), s._recursivelyAddChildrenToMap(a, t, n)), i = h.length - 1; i >= 0; i--) {
                o = h[i], n.contains(o._latlng) || r.removeLayer(o);
              }
            }), this._forceLayout(), this._topClusterLevel._recursivelyBecomeVisible(n, t), r.eachLayer(function (e) {
              e instanceof L.MarkerCluster || !e._icon || e.clusterShow();
            }), this._topClusterLevel._recursively(n, e, t, function (e) {
              e._recursivelyRestoreChildPositions(t);
            }), this._ignoreMove = !1, this._enqueue(function () {
              this._topClusterLevel._recursively(n, e, s, function (e) {
                r.removeLayer(e), e.clusterShow();
              }), this._animationEnd();
            });
          },
          _animationZoomOut: function _animationZoomOut(e, t) {
            this._animationZoomOutSingle(this._topClusterLevel, e - 1, t), this._topClusterLevel._recursivelyAddChildrenToMap(null, t, this._getExpandedVisibleBounds()), this._topClusterLevel._recursivelyRemoveChildrenFromMap(this._currentShownBounds, Math.floor(this._map.getMinZoom()), e, this._getExpandedVisibleBounds());
          },
          _animationAddLayer: function _animationAddLayer(e, t) {
            var i = this,
                n = this._featureGroup;
            n.addLayer(e), t !== e && (t._childCount > 2 ? (t._updateIcon(), this._forceLayout(), this._animationStart(), e._setPos(this._map.latLngToLayerPoint(t.getLatLng())), e.clusterHide(), this._enqueue(function () {
              n.removeLayer(e), e.clusterShow(), i._animationEnd();
            })) : (this._forceLayout(), i._animationStart(), i._animationZoomOutSingle(t, this._map.getMaxZoom(), this._zoom)));
          }
        },
        _animationZoomOutSingle: function _animationZoomOutSingle(e, t, i) {
          var n = this._getExpandedVisibleBounds(),
              r = Math.floor(this._map.getMinZoom());

          e._recursivelyAnimateChildrenInAndAddSelfToMap(n, r, t + 1, i);

          var s = this;
          this._forceLayout(), e._recursivelyBecomeVisible(n, i), this._enqueue(function () {
            if (1 === e._childCount) {
              var o = e._markers[0];
              this._ignoreMove = !0, o.setLatLng(o.getLatLng()), this._ignoreMove = !1, o.clusterShow && o.clusterShow();
            } else e._recursively(n, i, r, function (e) {
              e._recursivelyRemoveChildrenFromMap(n, r, t + 1);
            });

            s._animationEnd();
          });
        },
        _animationEnd: function _animationEnd() {
          this._map && (this._map._mapPane.className = this._map._mapPane.className.replace(" leaflet-cluster-anim", "")), this._inZoomAnimation--, this.fire("animationend");
        },
        _forceLayout: function _forceLayout() {
          L.Util.falseFn(document.body.offsetWidth);
        }
      }), L.markerClusterGroup = function (e) {
        return new L.MarkerClusterGroup(e);
      };
      var i = L.MarkerCluster = L.Marker.extend({
        options: L.Icon.prototype.options,
        initialize: function initialize(e, t, i, n) {
          L.Marker.prototype.initialize.call(this, i ? i._cLatLng || i.getLatLng() : new L.LatLng(0, 0), {
            icon: this,
            pane: e.options.clusterPane
          }), this._group = e, this._zoom = t, this._markers = [], this._childClusters = [], this._childCount = 0, this._iconNeedsUpdate = !0, this._boundsNeedUpdate = !0, this._bounds = new L.LatLngBounds(), i && this._addChild(i), n && this._addChild(n);
        },
        getAllChildMarkers: function getAllChildMarkers(e, t) {
          e = e || [];

          for (var i = this._childClusters.length - 1; i >= 0; i--) {
            this._childClusters[i].getAllChildMarkers(e);
          }

          for (var n = this._markers.length - 1; n >= 0; n--) {
            t && this._markers[n].__dragStart || e.push(this._markers[n]);
          }

          return e;
        },
        getChildCount: function getChildCount() {
          return this._childCount;
        },
        zoomToBounds: function zoomToBounds(e) {
          for (var t, i = this._childClusters.slice(), n = this._group._map, r = n.getBoundsZoom(this._bounds), s = this._zoom + 1, o = n.getZoom(); i.length > 0 && r > s;) {
            s++;
            var a = [];

            for (t = 0; t < i.length; t++) {
              a = a.concat(i[t]._childClusters);
            }

            i = a;
          }

          r > s ? this._group._map.setView(this._latlng, s) : o >= r ? this._group._map.setView(this._latlng, o + 1) : this._group._map.fitBounds(this._bounds, e);
        },
        getBounds: function getBounds() {
          var e = new L.LatLngBounds();
          return e.extend(this._bounds), e;
        },
        _updateIcon: function _updateIcon() {
          this._iconNeedsUpdate = !0, this._icon && this.setIcon(this);
        },
        createIcon: function createIcon() {
          return this._iconNeedsUpdate && (this._iconObj = this._group.options.iconCreateFunction(this), this._iconNeedsUpdate = !1), this._iconObj.createIcon();
        },
        createShadow: function createShadow() {
          return this._iconObj.createShadow();
        },
        _addChild: function _addChild(e, t) {
          this._iconNeedsUpdate = !0, this._boundsNeedUpdate = !0, this._setClusterCenter(e), e instanceof L.MarkerCluster ? (t || (this._childClusters.push(e), e.__parent = this), this._childCount += e._childCount) : (t || this._markers.push(e), this._childCount++), this.__parent && this.__parent._addChild(e, !0);
        },
        _setClusterCenter: function _setClusterCenter(e) {
          this._cLatLng || (this._cLatLng = e._cLatLng || e._latlng);
        },
        _resetBounds: function _resetBounds() {
          var e = this._bounds;
          e._southWest && (e._southWest.lat = 1 / 0, e._southWest.lng = 1 / 0), e._northEast && (e._northEast.lat = -1 / 0, e._northEast.lng = -1 / 0);
        },
        _recalculateBounds: function _recalculateBounds() {
          var e,
              t,
              i,
              n,
              r = this._markers,
              s = this._childClusters,
              o = 0,
              a = 0,
              h = this._childCount;

          if (0 !== h) {
            for (this._resetBounds(), e = 0; e < r.length; e++) {
              i = r[e]._latlng, this._bounds.extend(i), o += i.lat, a += i.lng;
            }

            for (e = 0; e < s.length; e++) {
              t = s[e], t._boundsNeedUpdate && t._recalculateBounds(), this._bounds.extend(t._bounds), i = t._wLatLng, n = t._childCount, o += i.lat * n, a += i.lng * n;
            }

            this._latlng = this._wLatLng = new L.LatLng(o / h, a / h), this._boundsNeedUpdate = !1;
          }
        },
        _addToMap: function _addToMap(e) {
          e && (this._backupLatlng = this._latlng, this.setLatLng(e)), this._group._featureGroup.addLayer(this);
        },
        _recursivelyAnimateChildrenIn: function _recursivelyAnimateChildrenIn(e, t, i) {
          this._recursively(e, this._group._map.getMinZoom(), i - 1, function (e) {
            var i,
                n,
                r = e._markers;

            for (i = r.length - 1; i >= 0; i--) {
              n = r[i], n._icon && (n._setPos(t), n.clusterHide());
            }
          }, function (e) {
            var i,
                n,
                r = e._childClusters;

            for (i = r.length - 1; i >= 0; i--) {
              n = r[i], n._icon && (n._setPos(t), n.clusterHide());
            }
          });
        },
        _recursivelyAnimateChildrenInAndAddSelfToMap: function _recursivelyAnimateChildrenInAndAddSelfToMap(e, t, i, n) {
          this._recursively(e, n, t, function (r) {
            r._recursivelyAnimateChildrenIn(e, r._group._map.latLngToLayerPoint(r.getLatLng()).round(), i), r._isSingleParent() && i - 1 === n ? (r.clusterShow(), r._recursivelyRemoveChildrenFromMap(e, t, i)) : r.clusterHide(), r._addToMap();
          });
        },
        _recursivelyBecomeVisible: function _recursivelyBecomeVisible(e, t) {
          this._recursively(e, this._group._map.getMinZoom(), t, null, function (e) {
            e.clusterShow();
          });
        },
        _recursivelyAddChildrenToMap: function _recursivelyAddChildrenToMap(e, t, i) {
          this._recursively(i, this._group._map.getMinZoom() - 1, t, function (n) {
            if (t !== n._zoom) for (var r = n._markers.length - 1; r >= 0; r--) {
              var s = n._markers[r];
              i.contains(s._latlng) && (e && (s._backupLatlng = s.getLatLng(), s.setLatLng(e), s.clusterHide && s.clusterHide()), n._group._featureGroup.addLayer(s));
            }
          }, function (t) {
            t._addToMap(e);
          });
        },
        _recursivelyRestoreChildPositions: function _recursivelyRestoreChildPositions(e) {
          for (var t = this._markers.length - 1; t >= 0; t--) {
            var i = this._markers[t];
            i._backupLatlng && (i.setLatLng(i._backupLatlng), delete i._backupLatlng);
          }

          if (e - 1 === this._zoom) for (var n = this._childClusters.length - 1; n >= 0; n--) {
            this._childClusters[n]._restorePosition();
          } else for (var r = this._childClusters.length - 1; r >= 0; r--) {
            this._childClusters[r]._recursivelyRestoreChildPositions(e);
          }
        },
        _restorePosition: function _restorePosition() {
          this._backupLatlng && (this.setLatLng(this._backupLatlng), delete this._backupLatlng);
        },
        _recursivelyRemoveChildrenFromMap: function _recursivelyRemoveChildrenFromMap(e, t, i, n) {
          var r, s;

          this._recursively(e, t - 1, i - 1, function (e) {
            for (s = e._markers.length - 1; s >= 0; s--) {
              r = e._markers[s], n && n.contains(r._latlng) || (e._group._featureGroup.removeLayer(r), r.clusterShow && r.clusterShow());
            }
          }, function (e) {
            for (s = e._childClusters.length - 1; s >= 0; s--) {
              r = e._childClusters[s], n && n.contains(r._latlng) || (e._group._featureGroup.removeLayer(r), r.clusterShow && r.clusterShow());
            }
          });
        },
        _recursively: function _recursively(e, t, i, n, r) {
          var s,
              o,
              a = this._childClusters,
              h = this._zoom;
          if (h >= t && (n && n(this), r && h === i && r(this)), t > h || i > h) for (s = a.length - 1; s >= 0; s--) {
            o = a[s], o._boundsNeedUpdate && o._recalculateBounds(), e.intersects(o._bounds) && o._recursively(e, t, i, n, r);
          }
        },
        _isSingleParent: function _isSingleParent() {
          return this._childClusters.length > 0 && this._childClusters[0]._childCount === this._childCount;
        }
      });
      L.Marker.include({
        clusterHide: function clusterHide() {
          var e = this.options.opacity;
          return this.setOpacity(0), this.options.opacity = e, this;
        },
        clusterShow: function clusterShow() {
          return this.setOpacity(this.options.opacity);
        }
      }), L.DistanceGrid = function (e) {
        this._cellSize = e, this._sqCellSize = e * e, this._grid = {}, this._objectPoint = {};
      }, L.DistanceGrid.prototype = {
        addObject: function addObject(e, t) {
          var i = this._getCoord(t.x),
              n = this._getCoord(t.y),
              r = this._grid,
              s = r[n] = r[n] || {},
              o = s[i] = s[i] || [],
              a = L.Util.stamp(e);

          this._objectPoint[a] = t, o.push(e);
        },
        updateObject: function updateObject(e, t) {
          this.removeObject(e), this.addObject(e, t);
        },
        removeObject: function removeObject(e, t) {
          var i,
              n,
              r = this._getCoord(t.x),
              s = this._getCoord(t.y),
              o = this._grid,
              a = o[s] = o[s] || {},
              h = a[r] = a[r] || [];

          for (delete this._objectPoint[L.Util.stamp(e)], i = 0, n = h.length; n > i; i++) {
            if (h[i] === e) return h.splice(i, 1), 1 === n && delete a[r], !0;
          }
        },
        eachObject: function eachObject(e, t) {
          var i,
              n,
              r,
              s,
              o,
              a,
              h,
              l = this._grid;

          for (i in l) {
            o = l[i];

            for (n in o) {
              for (a = o[n], r = 0, s = a.length; s > r; r++) {
                h = e.call(t, a[r]), h && (r--, s--);
              }
            }
          }
        },
        getNearObject: function getNearObject(e) {
          var t,
              i,
              n,
              r,
              s,
              o,
              a,
              h,
              l = this._getCoord(e.x),
              u = this._getCoord(e.y),
              _ = this._objectPoint,
              d = this._sqCellSize,
              c = null;

          for (t = u - 1; u + 1 >= t; t++) {
            if (r = this._grid[t]) for (i = l - 1; l + 1 >= i; i++) {
              if (s = r[i]) for (n = 0, o = s.length; o > n; n++) {
                a = s[n], h = this._sqDist(_[L.Util.stamp(a)], e), (d > h || d >= h && null === c) && (d = h, c = a);
              }
            }
          }

          return c;
        },
        _getCoord: function _getCoord(e) {
          var t = Math.floor(e / this._cellSize);
          return isFinite(t) ? t : e;
        },
        _sqDist: function _sqDist(e, t) {
          var i = t.x - e.x,
              n = t.y - e.y;
          return i * i + n * n;
        }
      }, function () {
        L.QuickHull = {
          getDistant: function getDistant(e, t) {
            var i = t[1].lat - t[0].lat,
                n = t[0].lng - t[1].lng;
            return n * (e.lat - t[0].lat) + i * (e.lng - t[0].lng);
          },
          findMostDistantPointFromBaseLine: function findMostDistantPointFromBaseLine(e, t) {
            var i,
                n,
                r,
                s = 0,
                o = null,
                a = [];

            for (i = t.length - 1; i >= 0; i--) {
              n = t[i], r = this.getDistant(n, e), r > 0 && (a.push(n), r > s && (s = r, o = n));
            }

            return {
              maxPoint: o,
              newPoints: a
            };
          },
          buildConvexHull: function buildConvexHull(e, t) {
            var i = [],
                n = this.findMostDistantPointFromBaseLine(e, t);
            return n.maxPoint ? (i = i.concat(this.buildConvexHull([e[0], n.maxPoint], n.newPoints)), i = i.concat(this.buildConvexHull([n.maxPoint, e[1]], n.newPoints))) : [e[0]];
          },
          getConvexHull: function getConvexHull(e) {
            var t,
                i = !1,
                n = !1,
                r = !1,
                s = !1,
                o = null,
                a = null,
                h = null,
                l = null,
                u = null,
                _ = null;

            for (t = e.length - 1; t >= 0; t--) {
              var d = e[t];
              (i === !1 || d.lat > i) && (o = d, i = d.lat), (n === !1 || d.lat < n) && (a = d, n = d.lat), (r === !1 || d.lng > r) && (h = d, r = d.lng), (s === !1 || d.lng < s) && (l = d, s = d.lng);
            }

            n !== i ? (_ = a, u = o) : (_ = l, u = h);
            var c = [].concat(this.buildConvexHull([_, u], e), this.buildConvexHull([u, _], e));
            return c;
          }
        };
      }(), L.MarkerCluster.include({
        getConvexHull: function getConvexHull() {
          var e,
              t,
              i = this.getAllChildMarkers(),
              n = [];

          for (t = i.length - 1; t >= 0; t--) {
            e = i[t].getLatLng(), n.push(e);
          }

          return L.QuickHull.getConvexHull(n);
        }
      }), L.MarkerCluster.include({
        _2PI: 2 * Math.PI,
        _circleFootSeparation: 25,
        _circleStartAngle: 0,
        _spiralFootSeparation: 28,
        _spiralLengthStart: 11,
        _spiralLengthFactor: 5,
        _circleSpiralSwitchover: 9,
        spiderfy: function spiderfy() {
          if (this._group._spiderfied !== this && !this._group._inZoomAnimation) {
            var e,
                t = this.getAllChildMarkers(null, !0),
                i = this._group,
                n = i._map,
                r = n.latLngToLayerPoint(this._latlng);
            this._group._unspiderfy(), this._group._spiderfied = this, t.length >= this._circleSpiralSwitchover ? e = this._generatePointsSpiral(t.length, r) : (r.y += 10, e = this._generatePointsCircle(t.length, r)), this._animationSpiderfy(t, e);
          }
        },
        unspiderfy: function unspiderfy(e) {
          this._group._inZoomAnimation || (this._animationUnspiderfy(e), this._group._spiderfied = null);
        },
        _generatePointsCircle: function _generatePointsCircle(e, t) {
          var i,
              n,
              r = this._group.options.spiderfyDistanceMultiplier * this._circleFootSeparation * (2 + e),
              s = r / this._2PI,
              o = this._2PI / e,
              a = [];

          for (s = Math.max(s, 35), a.length = e, i = 0; e > i; i++) {
            n = this._circleStartAngle + i * o, a[i] = new L.Point(t.x + s * Math.cos(n), t.y + s * Math.sin(n))._round();
          }

          return a;
        },
        _generatePointsSpiral: function _generatePointsSpiral(e, t) {
          var i,
              n = this._group.options.spiderfyDistanceMultiplier,
              r = n * this._spiralLengthStart,
              s = n * this._spiralFootSeparation,
              o = n * this._spiralLengthFactor * this._2PI,
              a = 0,
              h = [];

          for (h.length = e, i = e; i >= 0; i--) {
            e > i && (h[i] = new L.Point(t.x + r * Math.cos(a), t.y + r * Math.sin(a))._round()), a += s / r + 5e-4 * i, r += o / a;
          }

          return h;
        },
        _noanimationUnspiderfy: function _noanimationUnspiderfy() {
          var e,
              t,
              i = this._group,
              n = i._map,
              r = i._featureGroup,
              s = this.getAllChildMarkers(null, !0);

          for (i._ignoreMove = !0, this.setOpacity(1), t = s.length - 1; t >= 0; t--) {
            e = s[t], r.removeLayer(e), e._preSpiderfyLatlng && (e.setLatLng(e._preSpiderfyLatlng), delete e._preSpiderfyLatlng), e.setZIndexOffset && e.setZIndexOffset(0), e._spiderLeg && (n.removeLayer(e._spiderLeg), delete e._spiderLeg);
          }

          i.fire("unspiderfied", {
            cluster: this,
            markers: s
          }), i._ignoreMove = !1, i._spiderfied = null;
        }
      }), L.MarkerClusterNonAnimated = L.MarkerCluster.extend({
        _animationSpiderfy: function _animationSpiderfy(e, t) {
          var i,
              n,
              r,
              s,
              o = this._group,
              a = o._map,
              h = o._featureGroup,
              l = this._group.options.spiderLegPolylineOptions;

          for (o._ignoreMove = !0, i = 0; i < e.length; i++) {
            s = a.layerPointToLatLng(t[i]), n = e[i], r = new L.Polyline([this._latlng, s], l), a.addLayer(r), n._spiderLeg = r, n._preSpiderfyLatlng = n._latlng, n.setLatLng(s), n.setZIndexOffset && n.setZIndexOffset(1e6), h.addLayer(n);
          }

          this.setOpacity(.3), o._ignoreMove = !1, o.fire("spiderfied", {
            cluster: this,
            markers: e
          });
        },
        _animationUnspiderfy: function _animationUnspiderfy() {
          this._noanimationUnspiderfy();
        }
      }), L.MarkerCluster.include({
        _animationSpiderfy: function _animationSpiderfy(e, t) {
          var i,
              n,
              r,
              s,
              o,
              a,
              h = this,
              l = this._group,
              u = l._map,
              _ = l._featureGroup,
              d = this._latlng,
              c = u.latLngToLayerPoint(d),
              p = L.Path.SVG,
              f = L.extend({}, this._group.options.spiderLegPolylineOptions),
              m = f.opacity;

          for (void 0 === m && (m = L.MarkerClusterGroup.prototype.options.spiderLegPolylineOptions.opacity), p ? (f.opacity = 0, f.className = (f.className || "") + " leaflet-cluster-spider-leg") : f.opacity = m, l._ignoreMove = !0, i = 0; i < e.length; i++) {
            n = e[i], a = u.layerPointToLatLng(t[i]), r = new L.Polyline([d, a], f), u.addLayer(r), n._spiderLeg = r, p && (s = r._path, o = s.getTotalLength() + .1, s.style.strokeDasharray = o, s.style.strokeDashoffset = o), n.setZIndexOffset && n.setZIndexOffset(1e6), n.clusterHide && n.clusterHide(), _.addLayer(n), n._setPos && n._setPos(c);
          }

          for (l._forceLayout(), l._animationStart(), i = e.length - 1; i >= 0; i--) {
            a = u.layerPointToLatLng(t[i]), n = e[i], n._preSpiderfyLatlng = n._latlng, n.setLatLng(a), n.clusterShow && n.clusterShow(), p && (r = n._spiderLeg, s = r._path, s.style.strokeDashoffset = 0, r.setStyle({
              opacity: m
            }));
          }

          this.setOpacity(.3), l._ignoreMove = !1, setTimeout(function () {
            l._animationEnd(), l.fire("spiderfied", {
              cluster: h,
              markers: e
            });
          }, 200);
        },
        _animationUnspiderfy: function _animationUnspiderfy(e) {
          var t,
              i,
              n,
              r,
              s,
              o,
              a = this,
              h = this._group,
              l = h._map,
              u = h._featureGroup,
              _ = e ? l._latLngToNewLayerPoint(this._latlng, e.zoom, e.center) : l.latLngToLayerPoint(this._latlng),
              d = this.getAllChildMarkers(null, !0),
              c = L.Path.SVG;

          for (h._ignoreMove = !0, h._animationStart(), this.setOpacity(1), i = d.length - 1; i >= 0; i--) {
            t = d[i], t._preSpiderfyLatlng && (t.closePopup(), t.setLatLng(t._preSpiderfyLatlng), delete t._preSpiderfyLatlng, o = !0, t._setPos && (t._setPos(_), o = !1), t.clusterHide && (t.clusterHide(), o = !1), o && u.removeLayer(t), c && (n = t._spiderLeg, r = n._path, s = r.getTotalLength() + .1, r.style.strokeDashoffset = s, n.setStyle({
              opacity: 0
            })));
          }

          h._ignoreMove = !1, setTimeout(function () {
            var e = 0;

            for (i = d.length - 1; i >= 0; i--) {
              t = d[i], t._spiderLeg && e++;
            }

            for (i = d.length - 1; i >= 0; i--) {
              t = d[i], t._spiderLeg && (t.clusterShow && t.clusterShow(), t.setZIndexOffset && t.setZIndexOffset(0), e > 1 && u.removeLayer(t), l.removeLayer(t._spiderLeg), delete t._spiderLeg);
            }

            h._animationEnd(), h.fire("unspiderfied", {
              cluster: a,
              markers: d
            });
          }, 200);
        }
      }), L.MarkerClusterGroup.include({
        _spiderfied: null,
        unspiderfy: function unspiderfy() {
          this._unspiderfy.apply(this, arguments);
        },
        _spiderfierOnAdd: function _spiderfierOnAdd() {
          this._map.on("click", this._unspiderfyWrapper, this), this._map.options.zoomAnimation && this._map.on("zoomstart", this._unspiderfyZoomStart, this), this._map.on("zoomend", this._noanimationUnspiderfy, this), L.Browser.touch || this._map.getRenderer(this);
        },
        _spiderfierOnRemove: function _spiderfierOnRemove() {
          this._map.off("click", this._unspiderfyWrapper, this), this._map.off("zoomstart", this._unspiderfyZoomStart, this), this._map.off("zoomanim", this._unspiderfyZoomAnim, this), this._map.off("zoomend", this._noanimationUnspiderfy, this), this._noanimationUnspiderfy();
        },
        _unspiderfyZoomStart: function _unspiderfyZoomStart() {
          this._map && this._map.on("zoomanim", this._unspiderfyZoomAnim, this);
        },
        _unspiderfyZoomAnim: function _unspiderfyZoomAnim(e) {
          L.DomUtil.hasClass(this._map._mapPane, "leaflet-touching") || (this._map.off("zoomanim", this._unspiderfyZoomAnim, this), this._unspiderfy(e));
        },
        _unspiderfyWrapper: function _unspiderfyWrapper() {
          this._unspiderfy();
        },
        _unspiderfy: function _unspiderfy(e) {
          this._spiderfied && this._spiderfied.unspiderfy(e);
        },
        _noanimationUnspiderfy: function _noanimationUnspiderfy() {
          this._spiderfied && this._spiderfied._noanimationUnspiderfy();
        },
        _unspiderfyLayer: function _unspiderfyLayer(e) {
          e._spiderLeg && (this._featureGroup.removeLayer(e), e.clusterShow && e.clusterShow(), e.setZIndexOffset && e.setZIndexOffset(0), this._map.removeLayer(e._spiderLeg), delete e._spiderLeg);
        }
      }), L.MarkerClusterGroup.include({
        refreshClusters: function refreshClusters(e) {
          return e ? e instanceof L.MarkerClusterGroup ? e = e._topClusterLevel.getAllChildMarkers() : e instanceof L.LayerGroup ? e = e._layers : e instanceof L.MarkerCluster ? e = e.getAllChildMarkers() : e instanceof L.Marker && (e = [e]) : e = this._topClusterLevel.getAllChildMarkers(), this._flagParentsIconsNeedUpdate(e), this._refreshClustersIcons(), this.options.singleMarkerMode && this._refreshSingleMarkerModeMarkers(e), this;
        },
        _flagParentsIconsNeedUpdate: function _flagParentsIconsNeedUpdate(e) {
          var t, i;

          for (t in e) {
            for (i = e[t].__parent; i;) {
              i._iconNeedsUpdate = !0, i = i.__parent;
            }
          }
        },
        _refreshSingleMarkerModeMarkers: function _refreshSingleMarkerModeMarkers(e) {
          var t, i;

          for (t in e) {
            i = e[t], this.hasLayer(i) && i.setIcon(this._overrideMarkerIcon(i));
          }
        }
      }), L.Marker.include({
        refreshIconOptions: function refreshIconOptions(e, t) {
          var i = this.options.icon;
          return L.setOptions(i, e), this.setIcon(i), t && this.__parent && this.__parent._group.refreshClusters(this), this;
        }
      }), e.MarkerClusterGroup = t, e.MarkerCluster = i;
    }(window);
  }
};