/**
 * @class CM_Page_Abstract
 * @extends CM_Component_Abstract
 */
var CM_Page_Abstract = CM_Component_Abstract.extend({

  /** @type String */
  _class: 'CM_Page_Abstract',

  /** @type String[] */
  _stateParams: [],

  /** @type String|Null */
  _fragment: null,

  _ready: function() {
    var location = window.location;
    var params = queryString.parse(location.search);
    var state = _.pick(params, _.intersection(_.keys(params), this.getStateParams()));
    this.routeToState(state, location.pathname + location.search);

    CM_Component_Abstract.prototype._ready.call(this);
  },

  /**
   * @returns {String|Null}
   */
  getFragment: function() {
    return this._fragment;
  },

  /**
   * @returns {String[]}
   */
  getStateParams: function() {
    return this._stateParams;
  },

  /**
   * @param {Object} state
   * @param {String} fragment
   */
  routeToState: function(state, fragment) {
    this._fragment = fragment;
    this._changeState(state);
  },

  /**
   * @param {Object} state
   */
  _changeState: function(state) {
  }

});
