<?php

/**
 * Settings calendar module model class.
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 */
class Settings_Calendar_Module_Model extends Settings_Vtiger_Module_Model
{
	public static function getCalendarConfig($type)
	{
		$query = (new \App\Db\Query())
			->from('vtiger_calendar_config')
			->where(['type' => $type]);
		$dataReader = $query->createCommand()->query();
		$calendarConfig = [];
		while ($row = $dataReader->read()) {
			$calendarConfig[] = [
				'name' => $row['name'],
				'label' => $row['label'],
				'value' => $row['value'],
			];
		}
		$dataReader->close();
		if ($type == 'colors') {
			$calendarConfig = array_merge($calendarConfig, self::getPicklistValue());
		}
		return $calendarConfig;
	}

	public static function updateCalendarConfig($params)
	{
		\App\Db::getInstance()->createCommand()->update('vtiger_calendar_config', ['value' => $params['color']], ['name' => $params['id']])
			->execute();
		\App\Cache::clear();
		\App\Colors::generate('calendar');
	}

	public static function updateNotWorkingDays($params)
	{
		if (!empty($params['val']) && is_array($params['val'])) {
			$value = implode(';', $params['val']);
		} elseif (!is_array($params['val'])) {
			$value = $params['val'];
		} else {
			$value = null;
		}
		\App\Db::getInstance()->createCommand()->update('vtiger_calendar_config', ['value' => $value], ['name' => 'notworkingdays']
		)->execute();
	}

	public static function getNotWorkingDays()
	{
		$query = (new \App\Db\Query())
			->from('vtiger_calendar_config')
			->where(['name' => 'notworkingdays']);
		$row = $query->createCommand()->queryOne();
		$return = [];
		if (isset($row['value'])) {
			$return = explode(';', $row['value']);
		}
		return $return;
	}

	public static function getCalendarColorPicklist()
	{
		return ['activitytype'];
	}

	/**
	 * Get picklist values.
	 *
	 * @return array
	 */
	public static function getPicklistValue()
	{
		$keys = ['name', 'label', 'value', 'table', 'field'];
		$calendarConfig = [];
		foreach (self::getCalendarColorPicklist() as $picklistName) {
			$picklistValues = \App\Fields\Picklist::getValues($picklistName);
			foreach ($picklistValues as $picklistValueId => $picklistValue) {
				if (strpos($picklistValue['color'], '#') === false) {
					$picklistValue['color'] = '#' . $picklistValue['color'];
				}
				$calendarConfig[] = array_combine($keys, [
					'id' => $picklistValueId,
					'value' => $picklistValue[$picklistName],
					'color' => $picklistValue['color'],
					'table' => 'vtiger_' . $picklistName,
					'field' => $picklistName, ]);
			}
		}
		return $calendarConfig;
	}
}
