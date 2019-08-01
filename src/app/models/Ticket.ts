import {getUserRaw, sendSecureHeader} from '../services/login.service';
import {GLOBAL} from '../services/global';
import {HttpClient, HttpHeaders} from '@angular/common/http';

function scaleList() {
	return ['3',
		{'ticket.lowest': '1'},
		{'ticket.low': '2'},
		{'ticket.medium': '3'},
		{'ticket.high': '4'},
		{'ticket.highest': '5'}
	];
}

export function fields() {
	return {
		'title': ['input', 'text'],
		'summary': ['text'],
		'hd_queue_id|Desk': ['dropdown', '3', {'ICT support': '3'}, {'Applicatie beheer': '2'}, {'Business Intelligence': '4'}],
		'impact': ['dropdown', '5',
			{'Hele afdeling staat vast': '5'},
			{'Hele afdeling ondervind hinder': '6'},
			{'Deel afdeling staat vast': '17'},
			{'Deel afdeling ondervind hinder': '18'},
			{'1 persoon staat vast': '7'},
			{'1 persoon ondervind hinder': '8'},
			{'Overig: 1 persoon': '24'},
			{'Overig: deel afdeling': '25'},
			{'Overig: hele afdeling': '26'},
		],
		'owner|Wijs aan': ['field', 'User', 'FULL_NAME', false],
	};
}

function timeDropdownArray() {
	const array: any[] = ['dropdown', '0', {'ticket.notime': '0'}];
	for (let h = 0; h < 48; h++) {
		for (let m = 0; m < 60; m += 15) {
			if (h + m === 0) {
				continue;
			}
			const key = (h > 9 ? h : '0' + h) + 'h' + (m > 9 ? m : '0' + m) + 'm';
			const value = (h * 60 * 60) + (m * 60);
			const obj = {};
			obj[key] = value.toString();
			array.push(obj);
		}
	}
	return array;
}

export class IdValue {
	public id: string;
	public value: string;
}

export class Ticket {
	public actiontime: boolean;
	public begin_waiting_date: null | string;
	public close_delay_stat: boolean;
	public closedate: null | string;
	public content: string;
	public date: string;
	public date_creation: string;
	public date_mod: string;
	public entities_id: string;
	public global_validation: boolean;
	public id: number;
	public impact: 'lowest' | 'low' | 'medium' | 'high' | 'highest';
	public internal_time_to_own: null | string;
	public internal_time_to_resolve: null | string;
	public is_deleted: boolean;
	public itilcategories_id: string;
	public locations_id: null | string;
	public name: string;
	public ola_waiting_duration: string;
	public olalevels_id_ttr: string;
	public olas_id_tto: string;
	public olas_id_ttr: string;
	public priority: 'lowest' | 'low' | 'medium' | 'high' | 'highest';
	public requesttypes_id: string;
	public sla_waiting_duration: string;
	public slalevels_id_ttr: string;
	public slas_id_tto: string;
	public slas_id_ttr: string;
	public solve_delay_stat: string;
	public solvedate: null | string;
	public status_id: number;
	public status: 'new' | 'assign' | 'plan' | 'waiting' | 'solved' |
		'closed' | 'accepted' | 'observe' | 'evaluation' | 'approbation' |
		'test' | 'qualification';
	public takeintoaccount_delay_stat: string;
	public time_to_own: null | string;
	public time_to_resolve: null | string;
	public type: 'incident' | 'request';
	public urgency: 'lowest' | 'low' | 'medium' | 'high' | 'highest';
	public users_id_lastupdater: string;
	public users_id_recipient: string;
	public validation_percent: number;
	public waiting_duration: string;
	public users_id_recipient_id: string;
	public requested_users: any[];
	public assigned_users: any[];
	public assigned_groups: any[];
	public requested_groups: any[];
	public solutions: any[];
	public similar: Ticket[];
	public possibleSolutions: any[];

	constructor(name: string, content: string) {
		this.name = name;
		this.content = content;
	}
}

export function retrieveTicketLayout(httpClient: HttpClient,
									 then: (t: TicketCategory[]) => void = t => {
									 }, onError: (reason: any) => void = e => {
	}) {
	const thiz = this;
	sendSecureHeader((headers: HttpHeaders) => {
		httpClient.get<TicketCategory[]>(GLOBAL.custom + '/TicketForm', {headers, withCredentials: true}).toPromise()
			.then(categories => {
				TicketCategory.fixData(categories);
				then(categories);
			})
			.catch(onError);
	});
}

export class TicketCategory {
	public id: number;
	public category_i18n: string;
	public data: string | TicketSubCategory[];

	public static convertData(category: TicketCategory): TicketSubCategory[] {
		// @ts-ignore
		return JSON.parse(category.data);
	}

	public static fixData(categories: TicketCategory[]) {
		for (const category of categories) {
			category.data = TicketCategory.convertData(category);
			for (const subTypes of category.data) {
				for (const f of Object.keys(subTypes)) {
					subTypes[f] = {...subTypes[f], ...fields(), ...subTypes[f]};
				}
			}
		}
	}
}

export class TicketSubCategory extends Map<String, TicketSubCategoryField> {
}

export class TicketSubCategoryField extends Map<String, TicketSubCategoryType> {
}

export class TicketSubCategoryType extends Array<string | Map<string, number>> {
	0: 'dropdown' | 'field' | 'input' | 'text' | 'file' | 'pre' | 'empty';
}
