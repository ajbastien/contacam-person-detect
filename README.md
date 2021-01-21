# contacam-person-detect
ContaCam person detection with DeepQuest AI and OpenHAB item processing

**What this program does:**
When ContaCam identifies motion it will start the program.  The program sends each snapshot.jpg to *DeapQuest AI*.  *DeepQuest* will return the objects found.  The program is only looking for "person".  If a person is found the personDetections.txt file is updated.  The PHP program uses this file to highlight the videos with people.  Once ContaCam writes the detection video the program will end.  This method was done in order to reduce the amount of file processing.  If we continually analyze the video then the machine would have very high CPU usage all the time.

The program also copies 2 videos to the main camera directory which can be used as lastMotion.mp4/gif and lastPerson.gif.  It can also send item triggers to *OpenHAB* home control software.

**Notes:**
You will need a computer with a decent amount of RAM available, as Deepstack is using deep-learning models. Therefore donít expect to run this on a pi, but a spare laptop should do. I have an 8GB Windows machine running ContaCam and Docker DeepQuest AI.
I found the docker version to be faster than desktop version and not API key needed.

1. Install Docker
    *	Follow instructions on Docker website

2. Install DeepStack
	*   docker pull deepquestai/deepstack

3. Run DeepStack (This will run everytime docker starts, BE SURE docker stars automatically)
	docker run -d --restart always -e VISION-DETECTION=True -v localstorage:/datastore -p 80:5000 deepquestai/deepstack

4. Install NodeJS
	*   Follow instructions on NodeJS website

5. Copy *Detection* files to a directory (e.g. C:\nodejs\contaCamPrsnDetect) or clone the git repository

6. Install Node Modules
	*   cd to directory (e.g. C:\nodejs\contaCamPrsnDetect)
	*   npm i
	
7. Modify config.js
	*   config.cameraDirectory - Parent Directory of all Cameras - see Global Settings in ContaCam (BE SURE TO INCLUDE LAST \\) e.g. "C:\\ContaCam\\";
	*   config.logFilePath - log file path e.g. "C:\\nodejs\\contaCamPrsnDetect\\detect.log";
	*   config.snapshotFileName - ContaCam snapshot file. Should be "snapshot.jpg";
	*   config.personDetectionFileName - file to write detections too.  Should be 'personDetections.txt';
	*   config.itemsURL - OpenHAB Items URL or null;
	*   config.itemsURL2 - OpenHAB Items URL for server 2 (if you have 2) or null;
	*   config.aiUrl - URL of the DeepQuest Person detection server e.g. "http://192.168.0.199:82/v1/vision/detection";
	*   config.auMinConfidence - Minumum confidence accepted (max 1) e.g. 0.75

8. Test config changes - You should see a person detection JSON message
	*   npm test

9. Replace css from PHP.zip in C:\Program Files (x86)\ContaCam\microapache\htdocs\styles

10. Replace summarysnapshot.php from PHP.zip in C:\Program Files (x86)\ContaCam\microapache\htdocs

11. Replace css in each camera's \styles directory

12. Replace summarysnapshot.php in each camera's directory

13. Change each camera's advanced setting to Launch program on "Recording Start"
	*   Program: NodeJS executable
		*   e.g. C:\Program Files\nodejs\node.exe
	*   Parameters: path to appContCam.js camera_name openhab_items 
		*   e.g. "C:\nodejs\contaCamPrsnDetect\appContaCam.js" "%name%" FF_Outside_Out_Mot FF_Outside_Front_Camera

