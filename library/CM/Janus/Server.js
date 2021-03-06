/**
 * @class CM_Janus_Server
 * @extends CM_Frontend_JsonSerializable
 */
var CM_Janus_Server = CM_Frontend_JsonSerializable.extend({

  _class: 'CM_Janus_Server',

  /**
   * @returns {String}
   */
  getWebSocketAddress: function() {
    return this.get('webSocketAddress');
  },

  /**
   * @param {String} webSocketAddress
   */
  setWebSocketAddress: function(webSocketAddress) {
    this.set('webSocketAddress', webSocketAddress);
  },

  /**
   * @returns {String}
   */
  getWebSocketAddressSubscribeOnly: function() {
    return this.get('webSocketAddressSubscribeOnly');
  },

  /**
   * @param {String} webSocketAddressSubscribeOnly
   */
  setWebSocketAddressSubscribeOnly: function(webSocketAddressSubscribeOnly) {
    this.set('webSocketAddressSubscribeOnly', webSocketAddressSubscribeOnly);
  },

  /**
   * @returns {String[]}
   */
  getIceServerList: function() {
    return this.get('iceServerList');
  },

  /**
   * @param {String[]} iceServerList
   */
  setIceServerList: function(iceServerList) {
    this.set('iceServerList', iceServerList);
  }
});
