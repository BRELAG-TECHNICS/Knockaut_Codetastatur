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
		$this->RegisterVariableString("SECMODE", "Aktueller Modus", "", 3);

		$this->RegisterTimer("ClearCodeTimer", 0, 'BRELAG_SetClearCodeTimer($_IPS[\'TARGET\']);');
		$this->RegisterTimer("wrongCodeTimer", 0, 'BRELAG_ResetWronPWLED($_IPS[\'TARGET\']);');
		$this->RegisterTimer("checkSecurityMode", 15000, 'BRELAG_CheckSecurityMode($_IPS[\'TARGET\']);');

		$this->ConnectParent("{1252F612-CF3F-4995-A152-DA7BE31D4154}"); //DominoSwiss eGate
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

			$value = $data->Values->Value;

			if($command == 42) {
				if($value > 0) {
					$this->SetTimerInterval("ClearCodeTimer", $timerintervalMillisecond);
					$typedCode = GetValue($this->GetIDForIdent("CODE"));
					$codeOK = GetValue($this->GetIDForIdent("CODEOK"));
					switch($value) {
						case 1:
							if($codeOK) {
								SetValue($securityEnterPasswordId, $securityPassword);
								SetValue($securityModus, 0);
								SetValue($this->GetIDForIdent("CODE"), 0);
								SetValue($this->GetIDForIdent("CODEOK"), false);
								$this->SwitchLED(1, self::LED_ON);
								$this->SwitchLED(2, self::LED_OFF);
								$this->SwitchLED(3, self::LED_OFF);
							} else{
								$typedCode .= 1;
								SetValue($this->GetIDForIdent("CODE"), $typedCode);
							}
						break;
	
						case 2:
							if($codeOK) {
								SetValue($securityEnterPasswordId, $securityPassword);
								SetValue($securityModus, 1);
								SetValue($this->GetIDForIdent("CODE"), 0);
								SetValue($this->GetIDForIdent("CODEOK"), false);
								$this->SwitchLED(2, self::LED_ON);
								$this->SwitchLED(1, self::LED_OFF);
								$this->SwitchLED(3, self::LED_OFF);
							} else{
								$typedCode .= 2;
								SetValue($this->GetIDForIdent("CODE"), $typedCode);
							}
						break;
	
						case 4:
							if($codeOK) {
								SetValue($securityEnterPasswordId, $securityPassword);
								SetValue($securityModus, 2);
								SetValue($this->GetIDForIdent("CODE"), 0);
								SetValue($this->GetIDForIdent("CODEOK"), false);
								$this->SwitchLED(3, self::LED_ON);
								$this->SwitchLED(1, self::LED_OFF);
								$this->SwitchLED(2, self::LED_OFF);
							} else{
								$typedCode .= 3;
								SetValue($this->GetIDForIdent("CODE"), $typedCode);
							}
						break;
	
						case 8:
							$typedCode .= 4;
							SetValue($this->GetIDForIdent("CODE"), $typedCode);
						break;
	
						case 16:
							$typedCode .= 5;
							SetValue($this->GetIDForIdent("CODE"), $typedCode);
						break;
	
						case 32:
							$typedCode .= 6;
							SetValue($this->GetIDForIdent("CODE"), $typedCode);
						break;
	
						case 64:
							if($typedCode == $securityPassword) {
								SetValue($this->GetIDForIdent("CODE"), 0);
								SetValue($this->GetIDForIdent("CODEOK"), true);
							} else {
								SetValue($this->GetIDForIdent("CODE"), 0);
								SetValue($this->GetIDForIdent("CODEOK"), false);
								$this->SetTimerInterval("ClearCodeTimer", 0);
								$this->wrongCode();
							}
							
						break;
	
						case 128:
							SetValue($this->GetIDForIdent("CODE"), 0);
							SetValue($this->GetIDForIdent("CODEOK"), false);
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

	public function SwitchLED(int $LEDnumber, int $State) {
		$this->SetLED($LEDnumber - 1 + $State * 8);
	}

	public function wrongCode() {
		$this->SetLED(22);
		$this->SetTimerInterval("wrongCodeTimer", 2000);
	}

	public function ResetWronPWLED() {
		$this->SetTimerInterval("wrongCodeTimer", 0);
		SetValue($this->GetIDForIdent("CODE"), 0);
		$this->SwitchLED(7, self::LED_OFF);
	}

	public function CheckSecurityMode() {
		$securityGUID = "{17433113-1A92-45B3-F250-B5E426040E64}";
		$securityInstance = IPS_GetInstanceListByModuleID($securityGUID);
		$securityInstanceId = $securityInstance[0];
		$securityEnterPasswordId = IPS_GetObjectIDByIdent("Password", $securityInstanceId);
		$securityModus = IPS_GetObjectIDByIdent("Mode", $securityInstanceId);

		$mode = GetValue($this->GetIDForIdent("SECMODE"));

		if($mode != $securityModus) {
			SetValue($this->GetIDForIdent("SECMODE"), GetValue($securityModus));
			$LEDnumber = $securityModus + 1;
			if($securityModus == 0) {
				$this->SwitchLED($LEDnumber, 1);
			}
			if($securityModus == 1) {
				$this->SwitchLED($LEDnumber, 1);
			}
			if($securityModus == 2) {
				$this->SwitchLED($LEDnumber, 1);
			}
		}
	}

	/* 
		Um beim MaxFlex eine LED einzuschalten. Funktioniert nur wen der MaxFlex eine Stromspeisung beseitzt.
	*/
	public function SetLED(int $Value){
		$this->SendCommand(1, 43, $Value, 3);
	}

	public function SendCommand(int $Instruction, int $Command, int $Value, int $Priority) {
		// CheckNr 2942145
		$id = $this->ReadPropertyInteger("ID");
		return $this->SendDataToParent(json_encode(Array("DataID" => "{C24CDA30-82EE-46E2-BAA0-13A088ACB5DB}", "Instruction" => $Instruction, "ID" => $id, "Command" => $Command, "Value" => $Value, "Priority" => $Priority)));
	}

}
?>