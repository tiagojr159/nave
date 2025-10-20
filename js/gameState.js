// gameState.js - Estado global do jogo
const WORLD_W = 100000;
const WORLD_H = 100000;
const FOV = 900;
const MAX_DRAW_DIST = 20000;
const STAR_COUNT = 400;
const IMG_PATH = "images/";

// Estado do jogo
const ship = {
  x: 500, y: 500,
  heading: 0,
  pitch: 0,
  vel: 0,
  acc: 0,
  maxSpeed: 1800,
  turnRate: 120,
  boostEnergy: 100,
  boostRechargeRate: 0.2,
  boostConsumptionRate: 0.8,
  turboMultiplier: 3.0
};

const settings = {
  mouseSensitivity: 0.05,
  invertY: false,
  sfxVolume: 0.6,
  soundEnabled: true,
  graphicsQuality: 'medium',
  showHologram: true,
  showScanner: true,
  showWaypoints: true,
  showPlanetView: true
};

let keys = {};

// Cache de imagens
const cache = {};

// Waypoints
const waypoints = [
  {name: 'Estação Espacial', x: 20000, y: 20000, type: 'station'},
  {name: 'Cinturão de Asteroides', x: 70000, y: 30000, type: 'asteroids'},
  {name: 'Anel de Saturno', x: 80000, y: 80000, type: 'planet'},
  {name: 'Laboratório de Pesquisa', x: 30000, y: 70000, type: 'lab'},
  {name: 'Polo Norte de Marte', x: 40000, y: 60000, type: 'planet'}
];

let targetWaypoint = null;
let nearestPlanet = null;
let showMap = true;