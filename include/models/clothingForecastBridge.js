window._cf_getGPSCoords = function () {
  return typeof userLatitude !== "undefined" && userLatitude !== null
    ? { lat: userLatitude, lon: userLongitude }
    : null;
};
