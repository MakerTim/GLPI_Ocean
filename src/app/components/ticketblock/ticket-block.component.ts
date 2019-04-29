/* tslint:disable */
import {Component, EventEmitter, Input, OnDestroy, OnInit, Output} from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {isLoggedIn, isLoggingIn, sendSecureHeader} from '../../services/login.service';
import {GLOBAL} from '../../services/global';
import {statsToJson} from '../../services/cmd';
import {retrieveTicketLayout, TicketCategory} from '../../models/Ticket';
import {I18n} from '../../pipes/translator';
import {TicketInputChangeEvent, TicketInputComponent} from '../ticketinput/ticket-input.component';
import {readInput, sendAttachments} from '../../services/ticket.service';

declare var $: any;

@Component({
	selector: 'ticket-block',
	templateUrl: './ticket-block.component.html',
	styleUrls: ['./ticket-block.component.scss']
})
export class TicketBlockComponent implements OnInit, OnDestroy {

	@Input() public categories: TicketCategory[] = [];
	@Input() public debug: boolean;

	@Output() public onTicketCreate = new EventEmitter<number>();
	@Output() public onSubValueChange = new EventEmitter<TicketSubInputChangeEvent>();

	public screenshots: string[] = [];
	private title: string;
	public selectedMainCategory: any;
	public selectedSubCategory: any;

	public posting = false;
	public postingValue = 0;
	public lastTicketId = 0;
	public stageType: 'info' | 'success' | 'warning' | 'danger' = 'info';
	public stage: 'creating' | 'attaching' | 'finishing' | 'finished' | string = 'creating';

	constructor(
		private httpClient: HttpClient) {
	}

	ngOnInit() {
		const thiz = this;
		this.registerDocument();
		this.loadCategories();
		this.loadFileTranslationButton();
	}

	loadCategories() {
		const thiz = this;
		if (this.categories.length === 0) {
			retrieveTicketLayout(this.httpClient,
				categories => thiz.categories = categories,
				console.error);
		}
	}

	loadFileTranslationButton() {
		const sheet = document.styleSheets[0];
		const selector = '.custom-file-label:after';
		const style = 'content: "' + I18n.resolve('file.browse') + '" !important;';
		if ('insertRule' in sheet) {
			// @ts-ignore
			sheet.insertRule(selector + '{' + style + '}', 0);
		} else if ('addRule' in sheet) {
			// @ts-ignore
			sheet.addRule(selector, style, 0);
		}
	}

	registerDocument() {
		const thiz = this;
		this.title = document.title;
		document.title = 'GLPI Ocean Ticketing';
		document.onpaste = (event: any) => {
			const items = (event.clipboardData || event.originalEvent.clipboardData).items;
			for (const item of items) {
				if (item.kind === 'string' && item.type === 'text/plain') {
					item.getAsString(copyPasta => {
						copyPasta = copyPasta.trim();
						if (copyPasta.startsWith('Host Name:')) {
							thiz.logComputerStats(copyPasta);
						}
					});
				} else if (item.kind === 'file') {
					const blob = item.getAsFile();
					const reader = new FileReader();
					reader.onload = (loaded: any) => thiz.screenshots.push(loaded.target.result);
					reader.readAsDataURL(blob);
				}
			}
		};
	}

	floor(input: number) {
		return Math.floor(input);
	}

	logComputerStats(stats) {
		const thiz = this;
		sendSecureHeader(headers => {
			thiz.httpClient.post(GLOBAL.api + '/LogComputer', statsToJson(stats), {headers}).toPromise()
				.then(console.log)
				.catch(console.error);
		});
	}

	isLoggingIn() {
		return isLoggingIn();
	}

	isLoggedIn() {
		return isLoggedIn();
	}

	objectKeys(obj: any) {
		if (obj == null) {
			return [];
		}
		return Object.keys(obj);
	}

	nthKey(obj: any, nth: number = 0) {
		if (obj == null) {
			return null;
		}
		return obj[Object.keys(obj)[nth]];
	}

	ngOnDestroy() {
		this.unregisterDocument();
	}

	unregisterDocument() {
		document.onpaste = null;
		document.title = this.title;
	}

	removeScreenshot(target: Element) {
		if (target.classList.contains('fadeOut')) {
			return;
		}
		const thiz = this;
		const parent: Element[] = Array.from(target.parentElement.children);
		const indexOfHTMLElement = parent.indexOf(target);
		target = parent[indexOfHTMLElement];
		target.classList.add('shrinkOut');
		target.classList.remove('fadeIn');

		setTimeout(() => {
			target.classList.remove('shrinkOut');
			thiz.screenshots.splice(parent.indexOf(target) - 2, 1);
		}, 1100);
	}

	indexOf(categories: TicketCategory[], mainCat: TicketCategory) {
		return categories.indexOf(mainCat);
	}

	onValueInput(field: string, event: TicketInputChangeEvent) {
		this.onSubValueChange.emit(new TicketSubInputChangeEvent(event.input, event.value, field));
	}

	async onSubmit(form: HTMLFormElement) {
		this.posting = true;
		$('form').find('input, select, textarea').attr('disabled', 'disabled');
		const fieldsToSend = {};
		const attachments = {};

		const allInputs: HTMLSelectElement[] | HTMLInputElement[] | HTMLTextAreaElement[] = $(form).find('input:not(.form-ignore), select:not(.form-ignore), textarea:not(.form-ignore)');
		for (const element of allInputs) {
			try {
				let appendTo = fieldsToSend;
				let input = await readInput(element);
				if (input === null || input.name === null) {
					continue;
				}
				input.name = input.name.replace('attach:', '').replace('[]', '');

				if (element.name.indexOf('attach:') === 0) {
					appendTo = attachments;
				}

				if (element.name.endsWith('[]')) {
					if (appendTo[input.name] === undefined) {
						appendTo[input.name] = [];
					}
					appendTo[input.name].push(input);
					continue;
				}
				appendTo[input.name] = input.value;
			} catch (e) {
				console.error(element, e);
			}
		}

		this.submitMainTicket(fieldsToSend, attachments);
	}

	setErrorProgress(stageName: string) {
		this.stageType = 'danger';
		this.stage = stageName;
		this.postingValue = 100;
		setTimeout(() => {
			this.posting = false;
			$('form').find('input, select, textarea').removeAttr('disabled');
			this.stageType = 'info';
			this.stage = 'creating';
			this.postingValue = 0;
		}, 5000);
	}

	progressbarAdd(toAdd: number) {
		const thiz = this;
		for (let i = 0; i < toAdd; i++) {
			setTimeout(() => {
				if (thiz.postingValue < 100) {
					thiz.postingValue++;
				}
			}, i * 100);
		}
	}

	submitMainTicket(fieldsToSend, attachments) {
		this.progressbarAdd(10); // 10
		this.stageType = 'info';
		sendSecureHeader(headers => {
			const mainHeaders = headers.set('Type', 'mainpost');
			this.progressbarAdd(5); // 15
			this.httpClient.post<any>(GLOBAL.api + '/TicketForm', fieldsToSend, {headers: mainHeaders}).toPromise()
				.then(response => {
					this.progressbarAdd(10); // 25
					this.stageType = 'success';
					this.lastTicketId = response.id;
					this.submitSubTicket(headers, response.id, attachments);
				})
				.catch(response => {
					this.setErrorProgress('Error ' + response.error);
				});
		});
	}

	submitSubTicket(headers: HttpHeaders, idTicket, attachmentTypes: any[][]) {
		const thiz = this;
		this.progressbarAdd(5); // 30
		this.stage = 'attaching ... couting';

		let count = 0;
		for (const attachments in attachmentTypes) {
			count += attachmentTypes[attachments].length;
		}
		this.progressbarAdd(5); // 35
		this.stage = 'attaching ... ' + count;

		if (count === 0) {
			this.progressbarAdd(40); // 70
			this.stage = 'attaching 0 0';
			setTimeout(() => {
				thiz.finishTicket();
			}, 500);
		} else {
			sendAttachments(this.httpClient, idTicket, attachmentTypes, progress => {
				if (progress === null) {
					this.finishTicket();
				} else if (typeof progress === 'string') {
					this.stage = progress;
				} else if (typeof progress === 'number') {
					this.progressbarAdd(progress);
				}
			});
		}
	}

	finishTicket() {
		const thiz = this;
		this.postingValue = 85;
		this.stage = 'finishing' + Math.floor(Math.random() * 10);
		setTimeout(() => {
			this.onTicketCreate.emit(this.lastTicketId);
			thiz.stage = 'finished';
			thiz.stageType = 'info';
			this.postingValue = 100;
		}, 2000);
	}

	changeMainCategory() {
		this.selectedSubCategory = null;
	}

	changeSubCategory() {
	}
}

export class TicketSubInputChangeEvent extends TicketInputChangeEvent {
	public field: string;

	constructor(input: TicketInputComponent, value: any, field: string) {
		super(input, value);
		this.field = field;
	}
}
