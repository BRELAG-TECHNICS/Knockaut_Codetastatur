<?php

class MaxFlexCodepanel extends IPSModule {

	const LED_OFF = 0;
	const LED_ON = 1;
	const LED_BLINK = 2;

	public function Create(){
		//Never delete this line!
		parent::Create();
		
		//These lines are parsed on Symcon Startup or Instance creation
		//You cannot use variables here. Just static values.
		
		$this->RegisterPropertyInteger("ID", 1);
		$this->RegisterPropertyInteger("TimerInterval", 15);

		$this->RegisterVariableInteger("CODE", "Code", "", 1);
		$this->RegisterVariableBoolean("CODEOK", "Ist Code Ok?", "", 2);
		$this->RegisterVariableInteger("SECMODE", "Aktueller Modus", "", 3);

		$this->RegisterTimer("ClearCodeTimer", 0, 'BRELAG_SetClearCodeTimer($_IPS[\'TARGET\']);');
		$this->RegisterTimer("wrongCodeTimer", 0, 'BRELAG_ResetWronPWLED($_IPS[\'TARGET\']);');

		$this->ConnectParent("{1252F612-CF3F-4995-A152-DA7BE31D4154}"); //DominoSwiss eGate

		$securityGUID = "{17433113-1A92-45B3-F250-B5E426040E64}";
		$securityInstance = IPS_GetInstanceListByModuleID($securityGUID);
		$securityInstanceId = $securityInstance[0];
		$securityModusId = IPS_GetObjectIDByIdent("Mode", $securityInstanceId);
		$this->RegisterSecurityMode($securityModusId);
		
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
		
	}
	
	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();

	}

	public function ReceiveData($JSONString) {

		$data = json_decode($JSONString);
		
		$this->SendDebug("BufferIn", print_r($data->Values, true), 0);
		$id = $data->Values->ID;
		$command = $data->Values->Command;

		if($id == $this->ReadPropertyInteger("ID")) {
			// Hole das Passwort vom Alarmanlage Modul.
				$securityGUID = "{17433113-1A92-45B3-F250-B5E426040E64}";
				$securityInstance = IPS_GetInstanceListByModuleID($securityGUID);
				$securityInstanceId = $securityInstance[0];
				$securityPassword = IPS_GetProperty($securityInstanceId, "Password");
				$securityEnterPasswordId = IPS_GetObjectIDByIdent("Password", $securityInstanceId);
				$securityModus = IPS_GetObjectIDByIdent("Mode", $securityInstanceId);
			// Hole aus der Konfiguration den Timer interval und rechne in Millisekunden um.	
				$timerintervalSecond = $this->ReadPropertyInteger("TimerInterval");
				$timerintervalMillisecond = $timerintervalSecond * 1000;
				$arrayConfigurationFormJSON = IPS_GetConfigurationForm($securityInstanceId);
				$arrayConfigurationForm = json_decode($arrayConfigurationFormJSON, true);
				$arrayConfigurationFormMode = $arrayConfigurationForm['elements'][2]['columns'][1]['edit']['options'];

			$value = $data->Values->Value;

			if($command == 42) {
				if($value > 0) {
					$this->SetTimerInterval("ClearCodeTimer", $timerintervalMillisecond);
					$typedCode = GetValue($this->GetIDForIdent("CODE"));
					$codeOK = GetValue($this->GetIDForIdent("CODEOK"));
					switch($value) {
						case 1: // Nummer 1 und Aus
							if($codeOK) {
								$this->SetSecurityMode($securityEnterPasswordId, $securityPassword, $securityModus, $arrayConfigurationFormMode, 0, 1);
								$this->DeleteCode();
							} else{
								$typedCode .= 1;
								SetValue($this->GetIDForIdent("CODE"), $typedCode);
							}
						break;
	
						case 2: // Nummer 2 und Bereich 1
							if($codeOK) {
								$this->SetSecurityMode($securityEnterPasswordId, $securityPassword, $securityModus, $arrayConfigurationFormMode, 1, 2);
								$this->DeleteCode();
							} else{
								$typedCode .= 2;
								SetValue($this->GetIDForIdent("CODE"), $typedCode);
							}
						break;
	
						case 4: // Nummer 3 und Bereich 2
							if($codeOK) {
								$this->SetSecurityMode($securityEnterPasswordId, $securityPassword, $securityModus, $arrayConfigurationFormMode, 2, 3);
								$this->DeleteCode();
							} else{
								$typedCode .= 3;
								SetValue($this->GetIDForIdent("CODE"), $typedCode);
							}
						break;
	
						case 8: // Nummer 4
							if($codeOK) {
								$this->SetSecurityMode($securityEnterPasswordId, $securityPassword, $securityModus, $arrayConfigurationFormMode, 3, 4);
								$this->DeleteCode();
							} else{
								$typedCode .= 4;
								SetValue($this->GetIDForIdent("CODE"), $typedCode);
							}
						break;
	
						case 16: // Nummer 5
							if($codeOK) {
								$this->SetSecurityMode($securityEnterPasswordId, $securityPassword, $securityModus, $arrayConfigurationFormMode, 4, 5);
								$this->DeleteCode();
							} else{
								$typedCode .= 5;
								SetValue($this->GetIDForIdent("CODE"), $typedCode);
							}
						break;
	
						case 32: // Nummer 6
							if($codeOK) {
								$this->SetSecurityMode($securityEnterPasswordId, $securityPassword, $securityModus, $arrayConfigurationFormMode, 5, 6);
								$this->DeleteCode();
							} else{
								$typedCode .= 6;
								SetValue($this->GetIDForIdent("CODE"), $typedCode);
							}
						break;
	
						case 64: // Enter
							if($typedCode == $securityPassword) {
								SetValue($this->GetIDForIdent("CODE"), 0);
								SetValue($this->GetIDForIdent("CODEOK"), true);
							} else {
								$this->DeleteCode();
								$this->SetTimerInterval("ClearCodeTimer", 0);
								$this->wrongCode();
							}
							
						break;
	
						case 128: // delete
							$this->DeleteCode();
							$this->SetTimerInterval("ClearCodeTimer", 0);
						break;
					}
				}
			}

			
		}
	}

	public function SetClearCodeTimer() {
		SetValue($this->GetIDForIdent("CODE"), 0);
		SetValue($this->GetIDForIdent("CODEOK"), false);
		$this->SetTimerInterval("ClearCodeTimer", 0);
	}

	private function SwitchLED(int $LEDnumber, int $State) {
		$this->SetLED($LEDnumber - 1 + $State * 8);
	}

	private function wrongCode() {
		$this->SetLED(22);
		$this->SetTimerInterval("wrongCodeTimer", 2000);
	}

	public function ResetWronPWLED() {
		$this->SetTimerInterval("wrongCodeTimer", 0);
		SetValue($this->GetIDForIdent("CODE"), 0);
		$this->SwitchLED(7, self::LED_OFF);
	}

	private function SetLED(int $Value){
		$this->SendCommand(1, 43, $Value, 3);
	}

	public function SendCommand(int $Instruction, int $Command, int $Value, int $Priority) {
		// CheckNr 2942145
		$id = $this->ReadPropertyInteger("ID");
		return $this->SendDataToParent(json_encode(Array("DataID" => "{C24CDA30-82EE-46E2-BAA0-13A088ACB5DB}", "Instruction" => $Instruction, "ID" => $id, "Command" => $Command, "Value" => $Value, "Priority" => $Priority)));
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $securityGUID = "{17433113-1A92-45B3-F250-B5E426040E64}";
		$securityInstance = IPS_GetInstanceListByModuleID($securityGUID);
		$securityInstanceId = $securityInstance[0];
		$securityEnterPasswordId = IPS_GetObjectIDByIdent("Password", $securityInstanceId);
		$securityModusId = IPS_GetObjectIDByIdent("Mode", $securityInstanceId);
		$securityModus = GetValue($securityModusId);
		$mode = GetValue($this->GetIDForIdent("SECMODE"));

        switch ($SenderID) {
            case $securityModusId:
                if($mode != $securityModus) {
					SetValue($this->GetIDForIdent("SECMODE"), GetValue($securityModusId));
					$LEDnumber = $securityModus + 1;
					switch($securityModus) {
						case 0:
							$this->SwitchLED($LEDnumber, self::LED_ON);
							$this->SwitchLED(2, self::LED_OFF);
							$this->SwitchLED(3, self::LED_OFF);
						break;
		
						case 1:
							$this->SwitchLED($LEDnumber, self::LED_ON);
							$this->SwitchLED(1, self::LED_OFF);
							$this->SwitchLED(3, self::LED_OFF);
						break;
						
						case 2:
							$this->SwitchLED($LEDnumber, self::LED_ON);
							$this->SwitchLED(1, self::LED_OFF);
							$this->SwitchLED(2, self::LED_OFF);
						break;
					}
				}
            break;
        }
    }

	private function RegisterSecurityMode(int $ID) {
		$this->RegisterMessage($ID, 10603 /* VM_UPDATE */);
	}

	private function DeleteCode() {
		SetValue($this->GetIDForIdent("CODE"), 0);
		SetValue($this->GetIDForIdent("CODEOK"), false);
	}

	/**
	 * @param integer $securityEnterPasswordId Password variable ID.
	 * @param integer $securityModus Passwort from Property. 
	 * @param integer $securityModus ObjectID.
	 * @param array $arrayConfigurationFormMode ProfileID array.
	 * @param integer $sort Mode position.
	 * @param integer $LED LED number.
	 */
	private function SetSecurityMode($securityEnterPasswordId, $securityPassword, $securityModus, $arrayConfigurationFormMode, $sort, $LED) {
		SetValue($securityEnterPasswordId, $securityPassword);
		foreach($arrayConfigurationFormMode as $configurationFormModeValue) {
			if($configurationFormModeValue['sort'] == $sort) {
				$modeValue = $configurationFormModeValue['value'];
				SetValue($securityModus, $modeValue); // Change Mode
				$arrayLED = [1, 2, 3, 4, 5, 6];
				foreach($arrayLED as $buttonNumber) {
					$this->SwitchLED($buttonNumber, self::LED_OFF);
				}
				$this->SwitchLED($LED, self::LED_ON);
			}
		}
		$this->DeleteCode();
	}
}

?>