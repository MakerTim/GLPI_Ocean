export class Log {

	public id: number;
	public itemtype: string;
	public items_id: number;
	public itemtype_link: string;
	public linked_action: number;
	public user_name: string;
	public date_mod: string;
	public id_search_option: number;
	public old_value: string;
	public new_value: string;

	static getUserName(log: Log) {
		return log.user_name.replace(/\(\d+\)$/, '');
	}

	static getUserId(log: Log) {
		const userId = log.user_name.replace(/([^(]+) /, '');
		return userId.substring(1, userId.length - 1);
	}
}

export class SystemEvent {

	public id: number;
	public items_id: number;
	public type: string;
	public date: string;
	public service: string;
	public level: number;
	public message: string;
}
