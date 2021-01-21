const request = require("request")
const fs = require("fs")

var config = require('./config');

var myArgs = process.argv.slice(2);
const endTime = Date.now() + 50000
var timer = -1
var loop
var done = false

var cameraName = ""
var motionItems = []
var cameraDir = ""
var cameraDirDated = ""
var processPID = 0
var personDetected = false

if (process.pid) {
  processPID = process.pid
}

var startDate = new Date()

logToFile("Started: " + myArgs.toString())

if (myArgs < 1) {
  logToFile("   Arguments Missing: Camera Name, Motion Item")

} else {
  cameraName = myArgs[0]
  console.log("cameraName:" + cameraName)
  motionItems = myArgs.slice(1)
  cameraDir = config.cameraDirectory + cameraName + "\\"
  console.log("cameraDir:" + cameraDir)
  cameraDirDated = cameraDir + currentDateDirectory() + "\\"
  console.log("cameraDirDated:" + cameraDirDated)

  if (processPID == 0) processPID = cameraName

  startDate = findLastFileTime(cameraDirDated, "gif")
  logToFile("   Start GIF file date: " + startDate.toString())

  runLoop()

  loop = setInterval(runningCheck, 250);

}

async function runLoop() {

  running = true

  var result = 0

  result = await processFile()
  if (result >= 0) {
    console.log(cameraName + ": " + result)
  }

  const newDate = findLastFileTime(cameraDirDated, "gif")

  if (startDate.getTime() != newDate.getTime()) {
    logToFile("   Finished with new GIF file date: " + newDate.toString())
    done = true
  }

  running = false

}

function runningCheck() {
  if (Date.now() > endTime) {
    logToFile("   Finished by timeout")
    closeCleanup()

  } else if (done) {
    closeCleanup()

  } else if (!running) {
    runLoop()
  }
}

function closeCleanup() {

  cleanup()

  clearInterval(loop)

  moveLastActivity()

}

function moveLastActivity() {

  const lastGif = findLastFile(cameraDirDated, "gif")
  const lastMp4 = findLastFile(cameraDirDated, "mp4")

  logToFile(`   moveLastActivity ${lastMp4} & ${lastGif}`)

  const lastActivity = cameraDir + 'lastmotion'
  fs.copyFile(lastGif, lastActivity + '.gif', (err) => {
    if (err) {
      console.error(`ERROR: ${lastGif} was NOT copied`);
      logToFile(`   ERROR: ${lastGif} was NOT copied`)
    }
  });

  fs.copyFile(lastMp4, lastActivity + '.mp4', (err) => {
    if (err) {
      console.error(`ERROR: ${lastMp4} was NOT copied`);
      logToFile(`   ERROR: ${lastMp4} was NOT copied`)
    }
  });

  if (personDetected) {
    fs.copyFile(lastGif, cameraDir + 'lastperson.gif', (err) => {
      if (err) {
        console.error(`ERROR: ${lastGif} was NOT copied`);
        logToFile(`   ERROR: ${lastGif} was NOT copied`)
      }
    });
  }

}

function cleanup() {
  if (timer > 0) {
    logToFile("   Cleared " + cameraName + " timer " + timer)
    timer = -1
    motionItems.forEach(item => {
      sendState(item, "CLOSED")
    });
  }

}

function processFile() {

  return new Promise(resolve => {

    const filename = cameraDir + config.snapshotFileName

    image_stream = fs.createReadStream(filename)

    var form = { "image": image_stream }

    request.post({ url: config.aiUrl, formData: form }, function (err, res, body) {

      try {

        response = JSON.parse(body)

        predictions = response["predictions"]
        var personFound = 0

        for (var i = 0; i < predictions.length; i++) {

          if (predictions[i]["label"] == "person" && predictions[i]["confidence"] >= config.auMinConfidence) {
            personFound = personFound + 1
            personDetected = true

            const data = getFormattedTime() + "," + predictions[i]["confidence"] + "," + predictions[i]["x_min"] + "," + predictions[i]["y_min"] + "," + predictions[i]["x_max"] + "," + predictions[i]["y_max"]
            const personFile = cameraDirDated + config.personDetectionFileName

            fs.appendFile(personFile, data + '\r\n', function (err) {
              if (err) console.log('Save Error: ' + err);
            });
            logToFile("   Detection: " + data)

          }
        }

        if (personFound > 0) {
          if (timer < 0) {
            timer = Date.now() + 10000
            logToFile("   Set " + cameraName + " timer " + timer)
            motionItems.forEach(item => {
              sendState(item, "OPEN")
            });

          } else {
            timer = Date.now() + 10000
            console.log("Reset " + cameraName + " timer " + timer)

          }

        } else {
          if (timer > 0 && Date.now() > timer) {
            logToFile("   Cleared " + cameraName + " timer " + timer)
            timer = -1
            motionItems.forEach(item => {
              sendState(item, "CLOSED")
            });
          }

        }

        resolve(personFound)

      } catch (e) {
        console.log("Error in JSON - " + err + " body: " + body)
        resolve(0)
      }

    })

  })

}

async function sendState(itemName, state) {
  var args = {
    headers: {
      "Content-Type": "text/plain",
      "Accept": "application/json"
    },
    body: state
  };

  if (config.itemsURL !== null) {

    logToFile("   Set " + itemName + " to " + state)

    request.put(config.itemsURL + itemName + "/state", args, function (err, res, body) {

      try {

        //console.log("Body: " + body) 

      } catch (e) {
        console.log("Error - " + err + " body: " + body)
        resolve(0)
      }

    })
  }

  if (config.itemsURL2 !== null) {
    request.put(config.itemsURL2 + itemName + "/state", args, function (err, res, body) {

      try {

        //console.log("Body: " + body) 

      } catch (e) {
        console.log("Error - " + err + " body: " + body)
        resolve(0)
      }
    })
  }

}

function currentDateDirectory() {
  var d = new Date(),
    month = '' + (d.getMonth() + 1),
    day = '' + d.getDate(),
    year = d.getFullYear();

  if (month.length < 2)
    month = '0' + month;
  if (day.length < 2)
    day = '0' + day;

  return [year, month, day].join('\\');
}

function getFormattedDate() {
  var d = new Date(),
    month = '' + (d.getMonth() + 1),
    day = '' + d.getDate(),
    year = d.getFullYear();

  if (month.length < 2)
    month = '0' + month;
  if (day.length < 2)
    day = '0' + day;

  return [year, month, day].join('/');
}
function getFormattedTime() {
  var d = new Date(),
    hour = '' + d.getHours(),
    min = '' + d.getMinutes(),
    secs = '' + d.getSeconds();

  if (hour.length < 2)
    hour = '0' + hour;
  if (min.length < 2)
    min = '0' + min;
  if (secs.length < 2)
    secs = '0' + secs;

  return [hour, min, secs].join(':');
}

function getFormattedTimestamp() {
  return getFormattedDate() + "-" + getFormattedTime()
}

function logToFile(data) {
  console.log(data)
  fs.appendFile(config.logFilePath, getFormattedTimestamp() + ": " + processPID + " - " + data + '\r\n', { flag: "a+" }, function (err) {
    if (err) console.log('Save Error: ' + err);
  });

}

function createdDate(file) {
  const { birthtime } = fs.statSync(file)

  var date = new Date(birthtime);

  return date
}

function findLastFileTime(folder, findExt) {
  var lastTime = null

  fs.readdirSync(folder).forEach(file => {
    const filelen = file.length
    const fileext = file.substring(filelen - 3)

    if (fileext == findExt) {
      const filedate = createdDate(folder + '\\' + file)
      if (lastTime == null || lastTime.getTime() < filedate.getTime()) lastTime = filedate
    }
  });

  return lastTime

}

function findLastFile(folder, findExt) {
  var lastTime = null
  var lastFileName = null

  fs.readdirSync(folder).forEach(file => {
    const filelen = file.length
    const fileext = file.substring(filelen - 3)

    if (fileext == findExt) {
      const filedate = createdDate(folder + '\\' + file)
      if (lastTime == null || lastTime.getTime() < filedate.getTime()) {
        lastTime = filedate
        lastFileName = folder + '\\' + file
      }
    }
  });

  return lastFileName

}
