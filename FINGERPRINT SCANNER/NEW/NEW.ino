#include <WiFi.h>
#include <HTTPClient.h>
#include <Adafruit_Fingerprint.h>
#include <LiquidCrystal_I2C.h>
#include <Keypad.h>
#include <Base64.h>
#include <time.h>
#include <ArduinoJson.h>
#include "RTClib.h"

#define RXD_PIN 21  // RX pin for sensor
#define TXD_PIN 22  // TX pin for sensor

const char* ntpServer = "pool.ntp.org";
const long  gmtOffset_sec = 8 * 3600; // Adjust for your timezone (GMT+8 for the Philippines)
const int   daylightOffset_sec = 0;

const char* ssid = "LUCAS";           // WiFi SSID
const char* password = "virus101";    // WiFi password

Adafruit_Fingerprint finger(&Serial2);
LiquidCrystal_I2C lcd(0x27, 16, 2);

const byte ROWS = 4;
const byte COLS = 4;
char keys[ROWS][COLS] = {
    {'1', '2', '3', 'A'},
    {'4', '5', '6', 'B'},
    {'7', '8', '9', 'C'},
    {'*', '0', '#', 'D'}
};
byte rowPins[ROWS] = {23, 19, 18, 5};
byte colPins[COLS] = {17, 16, 4, 2};
Keypad keypad = Keypad(makeKeymap(keys), rowPins, colPins, ROWS, COLS);

bool attendanceModeActive = false; // Tracks if attendance mode is active

String getCurrentTime() {
    struct tm timeInfo;
    if (!getLocalTime(&timeInfo)) {
        Serial.println("Failed to obtain time");
        return "";
    }

    char timeBuffer[20];
    strftime(timeBuffer, sizeof(timeBuffer), "%Y-%m-%d %H:%M:%S", &timeInfo);
    return String(timeBuffer); // Returns time as "YYYY-MM-DD HH:MM:SS"
}

String getCurrentDay() {
    struct tm timeInfo;
    if (!getLocalTime(&timeInfo)) {
        Serial.println("Failed to obtain time");
        return "";
    }

    char dayBuffer[10];
    strftime(dayBuffer, sizeof(dayBuffer), "%A", &timeInfo); // Day as full name (e.g., "Monday")
    return String(dayBuffer);
}

bool isWithinSchedule(int teacherFingerprintId, String day, String currentTime) {
    HTTPClient http;
    String url = "https://cvsuimus.site/validate_schedule.php";
    String payload = "fingerprint_id=" + String(teacherFingerprintId) +
                     "&day=" + day +
                     "&current_time=" + currentTime;

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    int httpResponseCode = http.POST(payload);
    String response = http.getString();
    http.end();

    // Server should respond with "valid" if within schedule
    return (httpResponseCode == HTTP_CODE_OK && response == "valid");
}




void setup() {
    Serial.begin(115200);
    Serial2.begin(57600, SERIAL_8N1, RXD_PIN, TXD_PIN);
    Wire.begin(25,26);
    lcd.begin(16,2);
    lcd.init();
    lcd.backlight();
    
    lcd.print("Connecting WiFi...");
    WiFi.begin(ssid, password);
    configTime(gmtOffset_sec, daylightOffset_sec, ntpServer);
    printLocalTime(); // Verify time synchronization
   
    while (WiFi.status() != WL_CONNECTED) {
        lcd.print(".");
        delay(500);
    }
    lcd.clear();
    lcd.print("WiFi Connected");
    delay(1000);

    configTime(8 * 3600, 0, "pool.ntp.org", "time.nist.gov"); // Adjust time zone (8 * 3600 for UTC+8)
    delay(2000); // Wait for time synchronization


    lcd.clear();
    lcd.print("Checking Sensor...");
    delay(1000);
    listStoredFingerprints();
    if (finger.verifyPassword()) {
        lcd.clear();
        lcd.print("Sensor OK");
        delay(1000);
    } else {
        lcd.clear();
        lcd.print("Sensor Error");
        while (1); // Halt if sensor not detected
    }
}

void loop() {

    showMainMenu();
    char option = getKeypadInput();

    if (option == '1') {
        lcd.clear();
        enrollMenu();
    } else if (option == '2') {
        lcd.clear();
        startAttendanceMode();
    } else if (option == '3') {
      

      }else {
        lcd.clear();
        lcd.print("Invalid Option");
        delay(1000);
    }
}

// Function to print the current time
void printLocalTime() {
    struct tm timeinfo;
    if (!getLocalTime(&timeinfo)) {
        Serial.println("Failed to obtain time");
        return;
    }
    Serial.println(&timeinfo, "%A, %B %d %Y %H:%M:%S"); // Print formatted time
}

void listStoredFingerprints() {
    lcd.clear();
    lcd.print("Listing IDs...");
    delay(1000);

    for (int i = 1; i < 128; i++) { // Assuming a max of 127 IDs
        if (finger.loadModel(i) == FINGERPRINT_OK) {
            Serial.print("Fingerprint ID stored: ");
            Serial.println(i);
        }
    }
}

void clearSensorDatabase() {
    // Clear the fingerprint sensor's database
    lcd.clear();
    lcd.print("Clearing Sensor...");
    if (finger.emptyDatabase() == FINGERPRINT_OK) {
        Serial.println("Sensor database cleared.");
        lcd.clear();
        lcd.print("Sensor Cleared");
    } else {
        Serial.println("Failed to clear sensor database.");
        lcd.clear();
        lcd.print("Sensor Clear Failed");
        delay(2000);
        return;
    }

    // Send a request to reset the SQL database
    HTTPClient http;
    String url = "https://cvsuimus.site/reset_fingerprint_ids.php";

    http.begin(url);
    int httpResponseCode = http.GET();
    String response = http.getString();
    http.end();

    lcd.clear();
    if (httpResponseCode == HTTP_CODE_OK && response == "success") {
        Serial.println("SQL database cleared.");
        lcd.print("SQL Cleared");
    } else {
        Serial.println("Failed to clear SQL database.");
        Serial.print("HTTP Response Code: ");
        Serial.println(httpResponseCode);
        Serial.print("Server Response: ");
        Serial.println(response);
        lcd.print("SQL Clear Failed");
    }

    delay(2000);
    listStoredFingerprints();
}


void showMainMenu() {
    lcd.clear();
    lcd.print("1: Enroll");
    lcd.setCursor(0, 1);
    lcd.print("2: Attendance");
}

char getKeypadInput() {
    char key;
    do {
        key = keypad.getKey();
    } while (!key);
    return key;
}

void enrollMenu() {
    lcd.clear();
    lcd.print("1: Teacher");
    lcd.setCursor(0, 1);
    lcd.print("2: Student");

    char option = getKeypadInput();
    if (option == '1') {
        handleEnrollment(true); // Enroll teacher
    } else if (option == '2') {
        handleEnrollment(false); // Enroll student
    } else {
        lcd.clear();
        lcd.print("Invalid Input");
        delay(1000);
    }
}

String getKeypadInputString() {
    String input = "";
    lcd.setCursor(0, 1);
    while (true) {
        char key = keypad.getKey();
        if (key) {
            if (key == '#') { // '#' acts as the "Enter" key
                break;
            } else if (key == '*') { // '*' acts as the "Backspace" key
                if (input.length() > 0) {
                    input.remove(input.length() - 1); // Remove last character
                    lcd.setCursor(0, 1);
                    lcd.print("                "); // Clear line
                    lcd.setCursor(0, 1);
                    lcd.print(input); // Display updated input
                }
            } else if (key >= '0' && key <= '9') { // Only accept numeric inputs
                input += key;
                lcd.print(key); // Display key on the LCD
            }
        }
        delay(100); // Debounce delay
    }
    return input;
}

bool sendOTP(String enrollmentCode) {
    HTTPClient http;
    String url = "https://cvsuimus.site/send_otp.php";
    String payload = "enrollment_code=" + enrollmentCode; // 'enrollment_code'


    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    int httpResponseCode = http.POST(payload);
    String response = http.getString();
    http.end();

    // Debugging outputs
    Serial.println("=== Send OTP Debug Log ===");
    Serial.print("Payload: ");
    Serial.println(payload);
    Serial.print("HTTP Response Code: ");
    Serial.println(httpResponseCode);
    Serial.print("Server Response: ");
    Serial.println(response);
    Serial.println("=== End Debug Log ===");
    

    return (httpResponseCode == HTTP_CODE_OK && response.equals("otp_sent"));
}

bool validateOTP(String enrollmentCode, String otp) {
    HTTPClient http;
    String url = "https://cvsuimus.site/validate_otp.php";
    String payload = "enrollment_code=" + enrollmentCode + "&otp=" + otp; // Match with PHP

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    int httpResponseCode = http.POST(payload);
    String response = http.getString();
    http.end();

    // Debugging outputs
    Serial.println("=== Validate OTP Debug Log ===");
    Serial.print("Payload: ");
    Serial.println(payload);
    Serial.print("HTTP Response Code: ");
    Serial.println(httpResponseCode);
    Serial.print("Server Response: ");
    Serial.println(response);
    Serial.println("=== End Debug Log ===");

    return (httpResponseCode == HTTP_CODE_OK && response.equals("valid"));
}



void handleEnrollment(bool isTeacher) {
    lcd.clear();
    lcd.print(isTeacher ? "Enter Code:" : "Enter ID:");
    lcd.setCursor(0, 1);

    // Step 1: Get the reference ID (enrollment_code for teachers, student_id for students)
    String referenceId = getKeypadInputString();
    if (referenceId == "") {
        lcd.clear();
        lcd.print("Invalid Input");
        delay(2000);
        return;
    }

    // Step 2: Validate the reference ID with the database
    if (!validateEnrollmentInput(referenceId, isTeacher)) {
        lcd.clear();
        lcd.print("Invalid Reference");
        delay(2000);
        return;
    }

    // Step 3: Check if a fingerprint already exists for this ID
    if (checkFingerprintExists(referenceId, isTeacher)) {
        lcd.clear();
        lcd.print("Finger Exists");
        if (checkFingerprintExists(referenceId, false)) {
            lcd.clear();
            lcd.print("Exiting...");
            delay(2000);
            return;
        }
        lcd.setCursor(0, 1);
        lcd.print("1:Rescan 2:Exit");
        char choice = getKeypadInput();
        if (choice == '2') {
            lcd.clear();
            lcd.print("Exiting...");
            delay(2000);
            return; // Exit the enrollment process
        }

        if (choice == '1') {
            // Send OTP for verification
            lcd.clear();
            lcd.print("Sending OTP...");
            delay(2000);

            if (!sendOTP(referenceId)) {
                lcd.clear();
                lcd.print("OTP Failed");
                delay(2000);
                return;
            }

            // Ask for the OTP input
            lcd.clear();
            lcd.print("Enter OTP:");
            lcd.setCursor(0, 1);
            String otpInput = getKeypadInputString();
            if (!validateOTP(referenceId, otpInput)) {
                lcd.clear();
                lcd.print("Invalid OTP");
                delay(2000);
                return;
            }

            // Step 3.1: Delete the fingerprint ID from the sensor
            int fingerprintId = getFingerprintIdFromDB(referenceId, isTeacher); // Fetch fingerprint ID from database
            if (fingerprintId >= 0 && deleteFingerprintFromSensor(fingerprintId)) {
                lcd.clear();
                lcd.print("Finger Removed");
                delay(2000);
            } else {
                lcd.clear();
                lcd.print("Sensor Del Error");
                delay(2000);
                return;
            }

            // Step 3.2: Delete the fingerprint data from the database
            if (!deleteFingerprint(referenceId, isTeacher)) {
                lcd.clear();
                lcd.print("DB Del Error");
                delay(2000);
                return;
            }
        }
    }

    // Step 4: Capture and store the fingerprint
    if (captureAndSendFingerprint(referenceId, isTeacher)) {
        lcd.clear();
        lcd.print("Enroll Success");
    } else {
        lcd.clear();
        lcd.print("Enroll Failed");
    }

    delay(2000);
}



int getFingerprintIdFromDB(String referenceId, bool isTeacher) {
    HTTPClient http;
    String url = isTeacher
                 ? "https://cvsuimus.site/get_teacher_fingerprint_id.php?reference_id=" + referenceId
                 : "https://cvsuimus.site/get_student_fingerprint_id.php?reference_id=" + referenceId;

    http.begin(url);
    int httpResponseCode = http.GET();
    String response = http.getString();
    http.end();

    if (httpResponseCode == HTTP_CODE_OK) {
        return response.toInt(); // Parse the fingerprint ID
    }
    return -1; // Return -1 if the request fails
}

bool deleteFingerprintFromSensor(int fingerprintId) {
    int result = finger.deleteModel(fingerprintId); // Use Adafruit Fingerprint library
    return result == FINGERPRINT_OK;
}




bool checkFingerprintExists(String referenceId, bool isTeacher) {
    HTTPClient http;
    String url = isTeacher
                 ? "https://cvsuimus.site/check_teacher_fingerprint.php"
                 : "https://cvsuimus.site/check_student_fingerprint.php";

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    String payload = isTeacher
                     ? "enrollment_code=" + referenceId
                     : "student_id=" + referenceId;

    int httpResponseCode = http.POST(payload);
    String response = http.getString();
    response.trim();

    // Debugging
    Serial.println("=== Check Fingerprint Exists Debug Log ===");
    Serial.print("Payload: ");
    Serial.println(payload);
    Serial.print("HTTP Response Code: ");
    Serial.println(httpResponseCode);
    Serial.print("Server Response: ");
    Serial.println(response);
    Serial.println("=== End Debug Log ===");

    http.end();

    return (httpResponseCode == HTTP_CODE_OK && response == "exists");
}




bool deleteFingerprint(String referenceId, bool isTeacher) {
    HTTPClient http;
    String url = isTeacher
                 ? "https://cvsuimus.site/delete_teacher_fingerprint.php"
                 : "https://cvsuimus.site/delete_student_fingerprint.php";

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    String payload = isTeacher
                     ? "enrollment_code=" + referenceId
                     : "student_id=" + referenceId;

    int httpResponseCode = http.POST(payload);
    String response = http.getString(); // Get the response as a string
    response.trim(); // Trim the response

    // Debugging
    Serial.println("=== Delete Fingerprint Debug Log ===");
    Serial.print("Payload: ");
    Serial.println(payload);
    Serial.print("HTTP Response Code: ");
    Serial.println(httpResponseCode);
    Serial.print("Server Response: ");
    Serial.println(response);
    Serial.println("=== End Debug Log ===");

    http.end();

    return (httpResponseCode == HTTP_CODE_OK && response == "deleted");
}





bool validateEnrollmentInput(String referenceId, bool isTeacher) {
    HTTPClient http;
    String url = isTeacher
                 ? "https://cvsuimus.site/validate_teacher.php"
                 : "https://cvsuimus.site/validate_student.php";

    String payload = isTeacher
                     ? "enrollment_code=" + referenceId
                     : "student_id=" + referenceId;

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    int httpResponseCode = http.POST(payload);
    String response = http.getString();
    response.trim(); // Ensure no extra spaces or newlines
    http.end();

    // Debugging outputs
    Serial.println("=== Validate Enrollment Debug Log ===");
    Serial.print("Payload: ");
    Serial.println(payload);
    Serial.print("HTTP Response Code: ");
    Serial.println(httpResponseCode);
    Serial.print("Server Response: ");
    Serial.println(response);
    Serial.println("=== End Debug Log ===");

    // Check if the response is "valid"
    return (httpResponseCode == HTTP_CODE_OK && response.equals("valid"));
}



bool captureAndSendFingerprint(String referenceId, bool isTeacher) {
    HTTPClient http;

    // Notify the user to place their finger
    lcd.clear();
    lcd.print("Place Finger");
    delay(1000);

    int p = -1;
    while (p != FINGERPRINT_OK) {
        p = finger.getImage();
        if (p == FINGERPRINT_OK) {
            lcd.clear();
            lcd.print("Image Taken");
            delay(1000);
        } else if (p == FINGERPRINT_NOFINGER) {
            lcd.print(".");
        } else {
            lcd.clear();
            lcd.print("Error");
            delay(1000);
        }
    }

    p = finger.image2Tz(1);
    if (p != FINGERPRINT_OK) {
        lcd.clear();
        lcd.print("Conversion Failed");
        delay(2000);
        return false;
    }

    lcd.clear();
    lcd.print("Remove Finger");
    delay(2000);

    // Ask the user to place the same finger again
    lcd.clear();
    lcd.print("Place Same");
    lcd.setCursor(0, 1);
    lcd.print("Finger Again");
    delay(1000);

    p = -1;
    while (p != FINGERPRINT_OK) {
        p = finger.getImage();
        if (p == FINGERPRINT_OK) {
            lcd.clear();
            lcd.print("Image Taken");
            delay(1000);
        } else if (p == FINGERPRINT_NOFINGER) {
            lcd.print(".");
        } else {
            lcd.clear();
            lcd.print("Error");
            delay(1000);
        }
    }

    p = finger.image2Tz(2);
    if (p != FINGERPRINT_OK) {
        lcd.clear();
        lcd.print("Conversion Failed");
        delay(2000);
        return false;
    }

    // Create a model from the captured images
    if (finger.createModel() != FINGERPRINT_OK) {
        lcd.clear();
        lcd.print("Finger Mismatch");
        delay(2000);
        return false;
    }

    // Find the next available fingerprint ID
    int fingerprintId = findNextAvailableID();
    if (fingerprintId == -1 || finger.storeModel(fingerprintId) != FINGERPRINT_OK) {
        lcd.clear();
        lcd.print("Store Failed");
        delay(2000);
        return false;
    }

    // Encode fingerprint template as base64
    uint8_t templateData[512];
    int templateSize = finger.getModel(); // Retrieve the fingerprint template
    if (templateSize != FINGERPRINT_OK) {
        lcd.clear();
        lcd.print("Template Error");
        delay(2000);
        return false;
    }

    // Encode the template to Base64
    String base64Template = base64::encode(templateData, sizeof(templateData));

    // Prepare the payload
    String url = isTeacher
                 ? "https://cvsuimus.site/enroll_teacher.php"
                 : "https://cvsuimus.site/enroll_student.php";

    String payload = "reference_id=" + referenceId +
                 "&fingerprint_id=" + String(fingerprintId) +
                 "&fingerprint_template=" + base64Template;

// Debugging: Print the payload to Serial Monitor
Serial.println("Payload: " + payload);

// Send the fingerprint ID, template, and reference ID to the server
http.begin(url);
http.addHeader("Content-Type", "application/x-www-form-urlencoded");
int httpResponseCode = http.POST(payload);
String response = http.getString();
http.end();

Serial.print("HTTP Response Code: ");
Serial.println(httpResponseCode);
Serial.print("Server Response: ");
Serial.println(response);

    // Check if the server response indicates success
    return (httpResponseCode == HTTP_CODE_OK && response == "success");
}

void startAttendanceMode() {
    lcd.clear();
    lcd.print("Scan Teacher");
    int failedAttempts = 0;

    while (true) {
        int p = -1;

        // Step 1: Capture fingerprint image
        while (p != FINGERPRINT_OK) {
            p = finger.getImage();
            if (p == FINGERPRINT_OK) {
                lcd.clear();
                lcd.print("Image Taken");
                delay(1000);
                break; // Proceed to the next step
            } else if (p == FINGERPRINT_NOFINGER) {
                lcd.print(".");
            } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
                lcd.clear();
                lcd.print("Comm Error");
                delay(1000);
            } else if (p == FINGERPRINT_IMAGEFAIL) {
                lcd.clear();
                lcd.print("Imaging Error");
                delay(1000);
            } else {
                lcd.clear();
                lcd.print("Unknown Error");
                delay(1000);
            }
        }

        // Step 2: Convert image to fingerprint template
        p = finger.image2Tz();
        if (p != FINGERPRINT_OK) {
            lcd.clear();
            lcd.print("Conversion Failed");
            delay(2000);
            failedAttempts++;
            if (failedAttempts >= 3) {
                lcd.clear();
                lcd.print("3 fail");
                lcd.setCursor(0,1);
                lcd.print("attempts");
                delay(1000);
                handleFailsafe();
                return; // Exit the function after failsafe handling
            }
            continue; // Retry from the beginning
        }

        // Step 3: Search for the fingerprint in the internal database
        p = finger.fingerSearch();
        if (p == FINGERPRINT_OK) {
            int scannedFingerprintId = finger.fingerID;

            Serial.println("Fingerprint ID Found: " + String(scannedFingerprintId));

            // Step 4: Validate if the fingerprint belongs to a teacher
            if (validateTeacherFingerprint(scannedFingerprintId)) {
                lcd.clear();
                lcd.print("Teacher Found");
                Serial.println("Match found for Teacher with Fingerprint ID: " + String(scannedFingerprintId));
                delay(2000);

                // Exit the loop as the teacher fingerprint is validated
                handleTeacherSchedule(scannedFingerprintId);
                return; // Break out of the function entirely
            } else {
                lcd.clear();
                lcd.print("Not a Teacher");
                Serial.println("No match found for Teacher with Fingerprint ID: " + String(scannedFingerprintId));
                delay(2000);
                failedAttempts++;
                if (failedAttempts >= 3) {
                    lcd.clear();
                    lcd.print("3 fail");
                    lcd.setCursor(0,1);
                    lcd.print("attempts");
                    delay(1000);
                    handleFailsafe();
                    return; // Exit the function after failsafe handling
                }
                continue; // Restart the loop
            }
        } else if (p == FINGERPRINT_NOTFOUND) {
            lcd.clear();
            lcd.print("No Match");
            Serial.println("No fingerprint match found in internal database.");
            delay(2000);
            failedAttempts++;
            if (failedAttempts >= 3) {
                lcd.clear();
                lcd.print("3 fail");
                lcd.setCursor(0,1);
                lcd.print("attempts");
                delay(1000);
                handleFailsafe();
                return; // Exit the function after failsafe handling
            }
        } else {
            lcd.clear();
            lcd.print("Search Error");
            Serial.println("Error during fingerprint search.");
            delay(2000);
            failedAttempts++;
            if (failedAttempts >= 3) {
                lcd.clear();
                lcd.print("3 fail");
                lcd.setCursor(0,1);
                lcd.print("attempts");
                delay(1000);
                handleFailsafe();
                return; // Exit the function after failsafe handling
            }
        }
    }
}

bool markStudentsAbsent(String sectionCode) {
    HTTPClient http;
    String url = "https://cvsuimus.site/mark_students_absent.php";
    String payload = "section_code=" + sectionCode;

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    // Send the POST request
    int httpResponseCode = http.POST(payload);
    String response = http.getString();
    http.end();

    // Debugging outputs
    Serial.println("=== Mark Students Absent Debug Log ===");
    Serial.print("Payload: ");
    Serial.println(payload);
    Serial.print("HTTP Response Code: ");
    Serial.println(httpResponseCode);
    Serial.print("Server Response: ");
    Serial.println(response);
    Serial.println("=== End Debug Log ===");

    return (httpResponseCode == HTTP_CODE_OK && response.indexOf("successfully") >= 0);
}



void enterAttendanceMode(String sectionCode, int teacherFingerprintId) {
    lcd.clear();
    lcd.print("Attendance Open");
    logTeacherAttendance(teacherFingerprintId, sectionCode);
    lcd.setCursor(0, 1);
    lcd.print("Code:" + sectionCode);
    delay(2000);
    lcd.clear();
    lcd.print("Attendance Mode:");
    delay(1000);
  
    int failedAttempts = 0;

    while (true) {
        lcd.clear();
        lcd.print("Scan Finger");

        int p = finger.getImage();

        if (p == FINGERPRINT_NOFINGER) {
            delay(50);
            continue; // Keep waiting for a finger
        } else if (p != FINGERPRINT_OK) {
            lcd.clear();
            lcd.print("Error Scan");
            delay(2000);
            failedAttempts++;
            if (failedAttempts >= 3) {
                lcd.clear();
                lcd.print("3 fail");
                lcd.setCursor(0,1);
                lcd.print("attempts");
                delay(2000);
               if (handleAttendanceFailsafe(sectionCode, teacherFingerprintId)) {
                return;
               }
                 // Exit attendance mode after failsafe
            }
            continue; // Retry on scan error
        }
  
        p = finger.image2Tz();
        if (p != FINGERPRINT_OK) {
            lcd.clear();
            lcd.print("Conversion Error");
            delay(2000);
            failedAttempts++;
            if (failedAttempts >= 3) {
                lcd.clear();
                lcd.print("3 fail");
                lcd.setCursor(0,1);
                lcd.print("attempts");
                delay(2000);
                if (handleAttendanceFailsafe(sectionCode, teacherFingerprintId)) {
                return;
               }
                 // Exit attendance mode after failsafe
            }
            continue;
        }

        p = finger.fingerSearch();
        if (p == FINGERPRINT_OK) {
            int scannedFingerprintId = finger.fingerID;

            // Check if the scanned fingerprint belongs to the teacher
            if (scannedFingerprintId == teacherFingerprintId) {
                lcd.clear();
                lcd.print("Attendance Closed");
                logTeacherAttendance(teacherFingerprintId, sectionCode);
                markStudentsAbsent(sectionCode);
                delay(2000);
                return;
                // Exit the attendance mode
            }

            // Log attendance for the student
            String response = logAttendance(scannedFingerprintId, sectionCode);

            if (response.indexOf("Time-in logged successfully") >= 0) {
                lcd.clear();
                lcd.print("Logged: Time In");
            } else if (response.indexOf("Time-out logged successfully") >= 0) {
                lcd.clear();
                lcd.print("Logged: Time Out");
            } else {
                lcd.clear();
                lcd.print("Log Failed");
            }
            delay(2000);
            failedAttempts = 0; // Reset failed attempts on success
        } else {
            lcd.clear();
            lcd.print("No Match");
            delay(2000);
            failedAttempts++;
            if (failedAttempts >= 3) {
                lcd.clear();
                lcd.print("3 fail");
                lcd.setCursor(0,1);
                lcd.print("attempts");
                delay(2000);
                if (handleAttendanceFailsafe(sectionCode, teacherFingerprintId)) {
                return;
               }
                 // Exit attendance mode after failsafe

            }

        }
    }
}




bool handleAttendanceFailsafe(String sectionCode, int teacherFingerprintId) {
    lcd.clear();
    lcd.print("Enter Code:");
    String referenceId = getKeypadInputString();

    // Validate Teacher Failsafe
    if (validateEnrollmentInput(referenceId, true)) {
        lcd.clear();
        lcd.print("Teacher Valid");
        delay(2000);
        // Convert the enrollment code to teacher ID
        int teacherId = enrollmentCodeToId(referenceId);

        // Check if the provided teacher ID matches the current active teacher ID
        if (teacherId == teacherFingerprintId) {
            lcd.clear();
            lcd.print("Attendance Closed");
            delay(2000);
            logTeacherAttendance(teacherFingerprintId, sectionCode);
            markStudentsAbsent(sectionCode);      
            return true;
        } else {
            lcd.clear();
            lcd.print("Invalid Teacher");
            delay(2000);
        }
      
    }

    // Validate Student Failsafe
    if (validateEnrollmentInput(referenceId, false)) {
        lcd.clear();
        lcd.print("Student Valid");
        delay(2000);

        // Log attendance for the student using the student ID
        String response = logAttendance(getFingerprintId(referenceId, false),sectionCode);

        if (response.indexOf("Time-in logged successfully") >= 0) {
            lcd.clear();
            lcd.print("Logged: Time In");
        } else if (response.indexOf("Time-out logged successfully") >= 0) {
            lcd.clear();
            lcd.print("Logged: Time Out");
        } else {
            lcd.clear();
            lcd.print("Log Failed");
        }
        delay(2000);
        
    }
  
    lcd.clear();
    lcd.print("Invalid Code");
    delay(2000);
    return false;
    // If neither teacher nor student code is valid
    
}


int getFingerprintId(String referenceId, bool isTeacher) {
    HTTPClient http;
    String url = "https://cvsuimus.site/get_fingerprint_id.php";
    String payload = "reference_id=" + referenceId + "&is_teacher=" + String(isTeacher ? "1" : "0");

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    int httpResponseCode = http.POST(payload);
    String response = http.getString();
    http.end();

    // Debugging output
    Serial.println("=== Get Fingerprint ID Debug Log ===");
    Serial.print("Payload: ");
    Serial.println(payload);
    Serial.print("HTTP Response Code: ");
    Serial.println(httpResponseCode);
    Serial.print("Server Response: ");
    Serial.println(response);
    Serial.println("=== End Debug Log ===");

    // Parse response and return fingerprint ID
    if (httpResponseCode == HTTP_CODE_OK) {
        return response.toInt();
    } else {
        return -1; // Return -1 to indicate an error
    }
}



void handleTeacherSchedule(int teacherFingerprintId) {
    HTTPClient http;
    String url = "https://cvsuimus.site/get_schedule.php";
    String payload = "fingerprint_id=" + String(teacherFingerprintId);

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    int httpResponseCode = http.POST(payload);
    if (httpResponseCode == HTTP_CODE_OK) {
        String response = http.getString();
        Serial.println("Schedule Response: " + response);

        // Parse the schedule data
        DynamicJsonDocument doc(1024);
        DeserializationError error = deserializeJson(doc, response);

        if (error) {
            Serial.println("Failed to parse JSON: " + String(error.c_str()));
            lcd.clear();
            lcd.print("Schedule Error");
            delay(2000);
            return;
        }

        // Check if the response contains schedule data
        if (!doc.is<JsonArray>()) {
            Serial.println("Invalid schedule format.");
            lcd.clear();
            lcd.print("No Schedule Found");
            delay(2000);
            return;
        }

        JsonArray scheduleArray = doc.as<JsonArray>();
        if (scheduleArray.size() == 0) {
            Serial.println("No schedules for this time.");
            lcd.clear();
            lcd.print("No Active Schedule");
            delay(2000);
            return;
        }

        // Extract the first schedule (assuming one active schedule at a time)
        String sectionCode = scheduleArray[0]["section_code"].as<String>();
        String startTime = scheduleArray[0]["start_time"].as<String>();
        String endTime = scheduleArray[0]["end_time"].as<String>();
        String day = scheduleArray[0]["day"].as<String>();

        // Log current time
        struct tm timeinfo;
        if (getLocalTime(&timeinfo)) {
            char currentTimeStr[9];
            strftime(currentTimeStr, sizeof(currentTimeStr), "%H:%M:%S", &timeinfo);
            Serial.println("Current Time: " + String(currentTimeStr));
            Serial.println("Schedule Start Time: " + startTime);
            Serial.println("Schedule End Time: " + endTime);

            // Convert times to seconds since midnight for proper comparison
            int currentTimeSeconds = parseTimeToSeconds(String(currentTimeStr));
            int startTimeSeconds = parseTimeToSeconds(startTime);
            int endTimeSeconds = parseTimeToSeconds(endTime);

            // Compare current time with schedule times
            if (currentTimeSeconds >= startTimeSeconds && currentTimeSeconds <= endTimeSeconds) {
                lcd.clear();
                lcd.print("Attendance Start");
                enterAttendanceMode(sectionCode, teacherFingerprintId);
                return;
            }
        } else {
            Serial.println("Failed to fetch ESP32 time.");
        }

        lcd.clear();
        lcd.print("Out of Schedule");
        delay(2000);
    } else {
        Serial.println("Failed to fetch schedule. HTTP Code: " + String(httpResponseCode));
        lcd.clear();
        lcd.print("No Schedule");
        delay(2000);
    }

    http.end();
}

/**
 * Converts a time string in HH:mm:ss format to seconds since midnight.
 * 
 * @param timeStr The time string (e.g., "14:30:00").
 * @return The total seconds since midnight.
 */
int parseTimeToSeconds(String timeStr) {
    int hours = timeStr.substring(0, 2).toInt();
    int minutes = timeStr.substring(3, 5).toInt();
    int seconds = timeStr.substring(6, 8).toInt();
    return (hours * 3600) + (minutes * 60) + seconds;
}


// Validate if the student fingerprint matches a student in the section
bool validateStudentFingerprint(int fingerprintId, String sectionCode) {
    HTTPClient http;
    String url = "https://cvsuimus.site/validate_student.php";
    String payload = "fingerprint_id=" + String(fingerprintId) + "&section_code=" + sectionCode;

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    int httpResponseCode = http.POST(payload);
    String response = http.getString();
    http.end();

    return (httpResponseCode == HTTP_CODE_OK && response == "valid");
}

// Log attendance for a student
String logAttendance(int fingerprintId, String sectionCode) {
    HTTPClient http;
    String url = "https://cvsuimus.site/log_attendance.php";
    String payload = "fingerprint_id=" + String(fingerprintId) + "&section_code=" + sectionCode;

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    // Send the POST request
    int httpResponseCode = http.POST(payload);
    String response = http.getString();
    response.trim(); // Ensure no extra spaces or newlines
    http.end();

    // Debugging outputs
    Serial.println("=== Debug Log ===");
    Serial.print("Payload: ");
    Serial.println(payload);
    Serial.print("HTTP Response Code: ");
    Serial.println(httpResponseCode);
    Serial.print("Server Response (Trimmed): ");
    Serial.println(response);
    Serial.println("=== End Debug Log ===");

    // Check if the request was successful
    if (httpResponseCode == HTTP_CODE_OK) {
        return response; // Return the cleaned server response
    } else {
        return "error"; // Return error for failed request
    }
}




String logTeacherAttendance(int fingerprintId, String sectionCode) {
    HTTPClient http;
    String url = "https://cvsuimus.site/teacher_attendance.php";
    String payload = "fingerprint_id=" + String(fingerprintId) + "&section_code=" + sectionCode;

    // Debug: Print payload and URL
    Serial.println("Sending POST request to: " + url);
    Serial.println("Payload: " + payload);

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    // Send the POST request
    int httpResponseCode = http.POST(payload);

    // Get the response
    String response = http.getString();
    response.trim(); // Ensure no extra spaces or newlines
    http.end();

    // Debugging outputs
    Serial.println("=== Log Teacher Attendance Debug Log ===");
    Serial.print("Payload: ");
    Serial.println(payload);
    Serial.print("HTTP Response Code: ");
    Serial.println(httpResponseCode);
    Serial.print("Server Response (Trimmed): ");
    Serial.println(response);
    Serial.println("=== End Debug Log ===");

    // Return the response or error
    if (httpResponseCode > 0) {
        return response;
    } else {
        return "error";
    }
}










// Validate teacher fingerprint
bool validateTeacherFingerprint(int fingerprintId) {
    HTTPClient http;
    String url = "https://cvsuimus.site/validate_teacher_fingerprint.php";
    String payload = "fingerprint_id=" + String(fingerprintId);

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    int httpResponseCode = http.POST(payload);

    String response = ""; // Initialize as an empty string
    if (httpResponseCode > 0) {
        response = http.getString(); // Only fetch response if the HTTP code is valid
        response.trim(); // Remove any whitespace or newlines from the response
    } else {
        Serial.print("HTTP Error: ");
        Serial.println(httpResponseCode); // Log HTTP error for debugging
    }
    http.end();

    // Debugging outputs
    Serial.println("=== Validate Teacher Debug Log ===");
    Serial.print("Payload: ");
    Serial.println(payload);
    Serial.print("HTTP Response Code: ");
    Serial.println(httpResponseCode);
    Serial.print("Server Response: ");
    Serial.println(response);
    Serial.println("=== End Debug Log ===");

    // Check if response matches "valid"
    return (httpResponseCode == HTTP_CODE_OK && response == "valid");
}




void handleFailsafe() {
    lcd.clear();
    lcd.print("Enter Code:");
    String enrollmentCode = getKeypadInputString();

    // Step 1: Validate enrollment code in the database
    if (!validateEnrollmentInput(enrollmentCode, true)) {
        lcd.clear();
        lcd.print("Code Invalid");
        Serial.println("Failsafe failed, invalid enrollment code: " + enrollmentCode);
        delay(2000);
        return; // Exit the function if the code is invalid
    }

    lcd.clear();
    lcd.print("Code Valid");
    Serial.println("Failsafe activated with valid enrollment code: " + enrollmentCode);
    delay(2000);

    // Step 2: Convert enrollment code to teacher ID
    int teacherId = enrollmentCodeToId(enrollmentCode);
    if (teacherId < 0) { // Error occurred while fetching teacher ID
        lcd.clear();
        lcd.print("Teacher Not Found");
        Serial.println("Failed to find teacher ID for code: " + enrollmentCode);
        delay(2000);
        return;
    }

    // Step 3: Send OTP to the teacher's email
    lcd.clear();
    lcd.print("Sending OTP...");
    if (!sendOTP(enrollmentCode)) {
        lcd.clear();
        lcd.print("OTP Failed");
        Serial.println("Failed to send OTP for enrollment code: " + enrollmentCode);
        delay(2000);
        return; // Exit the function if OTP sending fails
    }

    lcd.clear();
    lcd.print("OTP Sent");
    delay(2000);

    // Step 4: Verify the OTP
    lcd.clear();
    lcd.print("Enter OTP:");
    lcd.setCursor(0, 1);
    String otpInput = getKeypadInputString();

    if (!validateOTP(enrollmentCode, otpInput)) {
        lcd.clear();
        lcd.print("Invalid OTP");
        Serial.println("Failsafe OTP validation failed for code: " + enrollmentCode);
        delay(2000);
        return; // Exit the function if OTP is invalid
    }

    lcd.clear();
    lcd.print("OTP Verified");
    Serial.println("Teacher Id: "+teacherId);
    Serial.println("Failsafe OTP verified for code: " + enrollmentCode);
    handleTeacherSchedule(teacherId); // Pass teacher ID to the schedule handler
    delay(2000);

    // Step 5: Proceed to teacher's schedule handling
    
}


int enrollmentCodeToId(String enrollmentCode) {
    HTTPClient http;
    String url = "https://cvsuimus.site/enrollment_code_to_id.php";
    String payload = "enrollment_code=" + enrollmentCode;

    http.begin(url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    int httpResponseCode = http.POST(payload);
    String response = http.getString();
    http.end();

    if (httpResponseCode == HTTP_CODE_OK) {
        return response.toInt(); // Convert response to integer (teacher_id)
    } else {
        Serial.println("Error fetching teacher ID for enrollment code: " + enrollmentCode);
        return -1; // Return -1 for failure
    }
}



int findNextAvailableID() {
    HTTPClient http;
    String url = "https://cvsuimus.site/find_next_id.php";

    http.begin(url);
    int httpResponseCode = http.GET();

    if (httpResponseCode == HTTP_CODE_OK) {
        String response = http.getString();
        http.end();

        Serial.print("Next available ID: ");
        Serial.println(response);

        return response.toInt();
    } else {
        Serial.println("Failed to get next ID");
        http.end();
        return -1;
    }
}

