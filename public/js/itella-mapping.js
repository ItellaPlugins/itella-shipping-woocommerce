"use strict";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var itellaMapping =
/*#__PURE__*/
function () {
  function itellaMapping(el) {
    _classCallCheck(this, itellaMapping);

    /* Itella Mapping version */
    this.version = '1.0.3';
    this._isDebug = false;
    /* default map center Lithuania Kaunas */

    this._defaultMapPos = [54.890926, 23.919338];
    /* zoom levels for map */

    this.ZOOM_DEFAULT = 8;
    this.ZOOM_SELECTED = 13;
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

    if (typeof L === 'undefined') {
      console.error(this.strings.error_leaflet);
    }
  }

  _createClass(itellaMapping, [{
    key: "setImagesUrl",
    value: function setImagesUrl(images_url) {
      this.images_url = images_url;
      return this;
    }
  }, {
    key: "setStrings",
    value: function setStrings(strings) {
      this.strings = _objectSpread({}, this.strings, {}, strings);
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
      /* if exists destroy and rebuild */

      if (this.UI.modal !== null) {
        this.UI.modal.parentNode.removeChild(this.UI.modal);
        this.UI.modal = null;
      }

      this.UI.modal = modal;
      this.UI.container.appendChild(this.UI.modal);
      this.UI.back_to_list_btn = this.UI.container.querySelector('.itella-btn.itella-back');
      return this;
    }
  }, {
    key: "buildContainer",
    value: function buildContainer() {
      var template = "\n      <div class=\"itella-chosen-point\">".concat(this.strings.select_pickup_point, "</div>\n      <a href='#' class=\"itella-modal-btn\">").concat(this.strings.select_pickup_point_btn, "</a>\n    ");
      var container = this.createElement('div', ['itella-shipping-container']);
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

          if (typeof _this.selectedPoint._marker._icon !== 'undefined') {
            _this.selectedPoint._marker._icon.classList.add('active');
          }
        }
      });
      this.UI.container.getElementsByClassName('search-input')[0].addEventListener('keyup', function (e) {
        e.preventDefault();
        var force = false;
        /* Enter key forces search to not wait */

        if (e.keyCode == '13') {
          force = true;
        }

        _this.searchNearestDebounce(this.value, force);
      });
      this.UI.container.getElementsByClassName('close-modal')[0].addEventListener('click', function (e) {
        e.preventDefault();

        _this.hideEl(_this.UI.modal);
      });
      this.UI.container.getElementsByClassName('itella-submit')[0].addEventListener('click', function (e) {
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

      this._markerLayer.on('click', function (e) {
        _this._markerLayer.eachLayer(function (icon) {
          L.DomUtil.removeClass(icon._icon, "active");
        });

        L.DomUtil.addClass(e.layer._icon, "active");

        _this.setMapView(e.layer.getLatLng(), _this._map.getZoom());

        var temp = _this.getLocationById(e.layer.options.pickupPointId);

        _this.renderPointInfo(temp);

        _this.selectedPoint = temp;

        if (_this._isDebug) {
          console.log('Selected pickup point ID:', temp, e.layer.getLatLng());
        }
      });

      return this;
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
      this.UI.container.getElementsByClassName('search-input')[0].value = '';
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
        return false;
      }

      return this.callbackList.push(callback);
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
      selectedEl.innerText = this.selectedPoint.publicName;
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
      drpd.innerText = location.publicName;
      return this;
    }
  }, {
    key: "setActiveMarkerByTerminalId",
    value: function setActiveMarkerByTerminalId(id) {
      this._markerLayer.eachLayer(function (icon) {
        if (typeof icon._icon !== 'undefined') {
          L.DomUtil.removeClass(icon._icon, "active");

          if (icon.options.pickupPointId === id) {
            L.DomUtil.addClass(icon._icon, "active");
          }
        }
      });
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
        minZoom: 4
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
      L.tileLayer('https://b.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.mijora.lt">Mijora</a>'
      }).addTo(this._map);
      this._markerLayer = L.featureGroup();

      this._map.addLayer(this._markerLayer);

      return this;
    }
  }, {
    key: "setMapView",
    value: function setMapView(targetLatLng, targetZoom) {
      if (window.matchMedia("(min-width: 769px)").matches) {
        var offset = this.getElClientWidth(this.UI.container.getElementsByClassName('itella-card')[0]) / 2;

        var targetPoint = this._map.project(targetLatLng, targetZoom).subtract([offset, 0]);

        targetLatLng = this._map.unproject(targetPoint, targetZoom);
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
        li.innerHTML = loc.publicName;

        if (typeof loc.distance != 'undefined') {
          var span = _this.createElement('span');

          span.innerText = loc.distance.toFixed(2);
          li.appendChild(span);
        }

        li.dataset.id = loc.id;
        listHTML.push(li);
        loc._li = li;
      }); //dropdown.append(listHTML);

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