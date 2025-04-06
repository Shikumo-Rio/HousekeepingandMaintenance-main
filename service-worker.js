const CACHE_NAME = "housekeeping-cache-v1";
const urlsToCache = [
  "/housekeepingandmaintenance-main/login.php", // Updated path
  "/housekeepingandmaintenance-main/styles.css", // Updated path
  "/housekeepingandmaintenance-main/scripts.js", // Updated path
  "/housekeepingandmaintenance-main/img/logo.webp", // Updated path
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(urlsToCache);
    })
  );
});

self.addEventListener("fetch", (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});
