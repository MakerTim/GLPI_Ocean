/* tslint:disable */
import {AfterViewInit, Component, ElementRef, EventEmitter, Input, OnDestroy, Output, ViewChild} from '@angular/core';
import {sendSecureHeader} from '../../services/login.service';
import {HttpClient} from '@angular/common/http';
import {GLOBAL} from '../../services/global';
import {IdValue} from 'src/app/models/Ticket';

@Component({
	selector: 'ticketinput',
	templateUrl: './ticket-input.component.html',
	styleUrls: ['./ticket-input.component.scss']
})
export class TicketInputComponent implements AfterViewInit, OnDestroy {

	@Input() public label: string;
	@Input() public debug: boolean;
	@Input() public typeArray: string[] | any[];

	@ViewChild('theInput') input: ElementRef;
	@Output() public valueChange = new EventEmitter<TicketInputChangeEvent>();

	public shownValue;
	public debugValue;

	public static dictionary = {};

	constructor(
		private httpClient: HttpClient) {
	}

	ngOnDestroy() {
		this.onChange(undefined);
	}

	ngAfterViewInit() {
		if (this.input !== undefined) {
			if (this.typeArray[0] === 'field' && !this.input.nativeElement.value) {
				return;
			}
			this.onChange(this.input.nativeElement.value);
		}
	}

	getDatabaseOptions(table: string, field: string) {
		const thiz = this;
		const key = table + '~' + field;
		if (key in TicketInputComponent.dictionary) {
			return TicketInputComponent.dictionary[key];
		}
		sendSecureHeader(headers => {
			headers = headers.set('Type', 'options')
				.set('Table', table)
				.set('Field', field);
			this.httpClient.get<IdValue[]>(GLOBAL.custom + '/TicketForm', {headers}).toPromise()
				.then(values => {
					TicketInputComponent.dictionary[key] = values;
					if (values.length > 0) {
						this.onChange(values[0].value);
					}
				})
				.catch(console.error);
		});
		return TicketInputComponent.dictionary[key] = [];
	}

	fixName(input: string) {
		return input.replace('ticket.', '').replace('custom.', '');
	}

	objectKeys(obj: any) {
		return Object.keys(obj);
	}

	subArray(array: any[]) {
		const copyArray = [...array];
		copyArray.shift();
		return copyArray;
	}

	onChange(value: any) {
		this.valueChange.emit(new TicketInputChangeEvent(this, value));
	}

	changeFile(target: any) {
		const filePath: string = target.value;
		let indexOf = -1;
		let testIndex;
		do {
			testIndex = filePath.indexOf('\\', indexOf + 1);
			if (testIndex >= 0) {
				indexOf = testIndex;
			}
		} while (testIndex >= 0);
		this.debugValue = filePath;
		this.shownValue = filePath.substring(indexOf + 1);

		// TODO:
		// onChange(target.value);
	}
}

export class TicketInputChangeEvent {

	public input: TicketInputComponent;
	public value: any;

	constructor(input: TicketInputComponent, value: any) {
		this.input = input;
		this.value = value;
	}
}
