export interface Permissions {
	users: string;
	user: string;
	meta: string;
	search: string;
	queues: string;
	queue: string;
	tickets: string;
	ticket: string;
	attachments: string;
	attachment: string;
	software: string;
	mi: string;
	scripts: string;
	script: string;
	assets: string;
	asset: string;
	barcodes: string;
	barcode: string;
	kbarticles: string;
	kbarticle: string;
	helpdesk: string;
	startupprograms: string;
	services: string;
	processes: string;
	machines: string;
	machine: string;
	alerts: string;
	alert: string;
	emailalerts: string;
	emailalert: string;
	monitoringalerts: string;
	monitoringalert: string;
	monitoringdevices: string;
	monitoringdevice: string;
	alertnotifications: string;
	alertnotification: string;
	kbarticle_USERPORTAL: string;
	kbarticles_USERPORTAL: string;
	helpdesk_USERPORTAL: string;
	mycomputer_USERPORTAL: string;
	attachment_USERPORTAL: string;
}

export interface LicensedFeatures {
	rf_adminui_scripting: boolean;
	rf_adminui_help_desk: boolean;
	rf_adminui_knowledge_base: boolean;
	rf_adminui_asset: boolean;
	rf_adminui_distribution: boolean;
	rf_adminui_inventory: boolean;
	rf_userui_help_desk: boolean;
	rf_userui_knowledge_base: boolean;
	rf_adminui_monitoring_alerts: boolean;
}

export interface CurrentOrgId {
	ID: string;
	NAME: string;
	DESCRIPTION: string;
	MODIFIED: string;
	CREATED: string;
	ROLE_ID: string;
	ACTIVE: string;
	DB: string;
	ORG_USER: string;
	REPORT_USER: string;
}

export interface SelfRequest {
	userId: string;
	permissions: Permissions;
	canAddTickets: boolean;
	canAddTicketsUserPortal: boolean;
	licensedFeatures: LicensedFeatures;
	supportAvailable: string;
	deviceScope: any[];
	loggedin: string;
	loggedinId: string;
	loggedinEmail: string;
	loggedinFullName: string;
	org_count: string;
	multiple_org_ui: boolean;
	orgs: any[];
	currentOrgId: CurrentOrgId;
	serialNumber: string;
	localTimezone: string;
	RESTAPIVersion: number;
	defaultQueueID: string;
	apiEnabled: string;
}
