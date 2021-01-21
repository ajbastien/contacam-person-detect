var config = {};

config.cameraDirectory = "D:\\ContaCam\\";
config.logFilePath = "D:\\nodejs\\contaCamPrsnDetect\\appContaCam.log"

config.snapshotFileName = "snapshot.jpg";
config.personDetectionFileName = 'personDetections.txt';

config.itemsURL = "http://192.168.0.200:8080/rest/items/";
config.itemsURL2 = null;

config.aiUrl = "http://192.168.0.199:82/v1/vision/detection";
config.auMinConfidence = 0.75

module.exports = config;
