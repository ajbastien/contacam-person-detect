var request = require('request');
const fs = require("fs")

var config = require('./config');

const filename = 'mypic.jpg'
image_stream = fs.createReadStream(filename)

var form = {"image":image_stream}

console.log (JSON.stringify(form))

request.post({url:config.aiUrl, formData:form},function(err,res,body){
  
    try {

      response = JSON.parse(body)

      predictions = response["predictions"]
      var personFound = 0

      for(var i =0; i < predictions.length; i++){

        //console.log(predictions[i]["label"] + " - " + predictions[i]["confidence"])
        if (predictions[i]["label"] == "person" && predictions[i]["confidence"] >= config.auMinConfidence) {
          personFound = personFound + 1

          const data = getFormattedTime() + "," +  predictions[i]["confidence"] + "," + predictions[i]["x_min"] + "," + predictions[i]["y_min"] + "," + predictions[i]["x_max"] + "," + predictions[i]["y_max"] + '\r\n'
          const personFile = dirname + currentDateDirectory() + '\\' + config.personDetectionFileName

          fs.appendFile(personFile, data, function (err) {
            if (err) console.log('Save Error: ' + err);
          });
          console.log(data)

        }
      }

      let timer = timers.get(camera.name)
      if (personFound > 0) {
        if (timer < 0) {
          console.log("Set " + camera.name + " timer")
          camera.motion.forEach(item => {
            sendState(item, "OPEN")
          });
        }
        timers.set(camera.name, Date.now() + 20000)
        console.log("Reset " + camera.name + " timer")
      }


    } catch (e) {
      console.log("Error in JSON - " + err + " body: " + body)
    }

  })

