/* tslint:disable:max-line-length forin */
import {NgbModal, NgbModalOptions} from '@ng-bootstrap/ng-bootstrap';
import {Ticket} from '../models/Ticket';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {sendSecureHeader} from './login.service';
import {GLOBAL} from './global';
import {I18n} from '../pipes/translator';

function open(modalService: NgbModal, followupHTML, options: NgbModalOptions = {centered: true}) {
	return modalService.open(followupHTML, options);
}

export function followup(modalService: NgbModal, followupHTML, httpClient: HttpClient, queue: number, ticket: number, triggerPost) {
	open(modalService, followupHTML).result
		.then(result => {
			if (!result || result.toString().trim().length === 0) {
				followup(modalService, followupHTML, httpClient, queue, ticket, triggerPost);
				return;
			}

			sendSecureHeader(headers => {
				// TODO: voeg notitie toe
				httpClient.post(GLOBAL.custom + '/ajax_update_ticket.php', {
						QUEUE_ID: queue,
						TICKET_ID: ticket,
						UPDATE_TYPE: 'add_comment',
						COMMENT: result,
						SCREENSHOT_DATA: '',
						OWNERS_ONLY: 0
					},
					{headers, withCredentials: true}).toPromise()
					.then(ticketResult => triggerPost(ticketResult));
			});
		})
		.catch(_ => {
		});
}

export function newInternalCategory(modalService: NgbModal, newCategoryHTML, httpClient: HttpClient, triggerPost) {
	open(modalService, newCategoryHTML).result
		.then(result => {
			console.log('A');
			if (!result || result.toString().trim().length === 0) {
				newInternalCategory(modalService, newCategoryHTML, httpClient, triggerPost);
				return;
			}
			console.log('B');
			sendSecureHeader(headers => {
				console.log('C');
				headers = headers.set('Type', 'newCategory')
					.set('category', result);
				httpClient.post(GLOBAL.api + '/Ticket', result, {headers}).toPromise()
					.then(ticketResult => triggerPost(ticketResult));
			});
		}).catch(_ => {
	});
}


export function solution(modalService: NgbModal, followupHTML, httpClient: HttpClient, ticket: Ticket, option: NgbModalOptions,
						 triggerPost: (result: any[], ticketResult: any) => void) {
	open(modalService, followupHTML, option).result
		.then((result: any[]) => {
			if (!result
				|| result.length === 0
				|| result[0].toString().trim().length === 0
				|| !result[1]) {
				solution(modalService, followupHTML, httpClient, ticket, option, triggerPost);
				return;
			}

			sendSecureHeader(headers => {
				headers = headers.set('Type', 'solution');
				headers = headers.set('id', ticket.id.toString());
				httpClient.post(GLOBAL.api + '/Ticket', result[0], {headers}).toPromise()
					.then(ticketResult => triggerPost(result, ticketResult));
			});
		}).catch(_ => {
	});
}

export function markSolution(modalService: NgbModal, followupHTML, httpClient: HttpClient, ticket: Ticket, accepted: boolean, triggerPost) {
	open(modalService, followupHTML).result
		.then(result => {
			if (!result) {
				result = '';
			}

			sendSecureHeader(headers => {
				headers = headers.set('Type', 'mark')
					.set('id', ticket.id.toString())
					.set('Approved', accepted ? '1' : '0');
				httpClient.post(GLOBAL.api + '/Ticket', result, {headers}).toPromise()
					.then(ticketResult => triggerPost(ticketResult));
			});
		}).catch(_ => {
	});
}

export function readInput(inputElement: HTMLSelectElement | HTMLInputElement | HTMLTextAreaElement, name: string = inputElement.name): Promise<{ name: string, value: string } | null> {
	if (inputElement.type === 'file' && inputElement instanceof HTMLInputElement) {
		const files: FileList = inputElement.files;
		if (!files || files.length === 0) {
			return new Promise<null>(resolve => {
				resolve(null);
			});
		}
		// @ts-ignore
		if (!window.File || !window.FileReader || !window.FileList || !window.Blob) {
			alert(I18n.resolve('ticket.fileNotSupported'));
		}
		for (let i = 0; i < files.length; i++) {
			const file = files.item(i);
			if (!file) {
				continue;
			}
			if (file.size >= 5266500 /* ~5mb */) {
				this.setErrorProgress('fileToBig');
				throw new Error('file to big ' + file.size);
			}
			return new Promise<{ name: string, value: string }>(resolve => {
				const fileReader = new FileReader();
				fileReader.onload = function (event: any) {
					resolve({name: file.name, value: event.target.result});
				};
				fileReader.readAsDataURL(file);
			});
		}
	}
	return new Promise<{ name: string, value: string }>(resolve =>
		resolve({name, value: inputElement.value}));
}

export function closeTicket(httpClient: HttpClient, ticket: Ticket | number, callback: () => void) {
	if (typeof ticket === 'object') {
		ticket = ticket.id;
	}

	sendSecureHeader(headers => {
		headers = headers.set('type', 'delete')
			.set('id', ticket.toString());
		httpClient.get(GLOBAL.api + '/Ticket', {headers})
			.toPromise().then(result => {
			callback();
		});
	});
}

export function sendAttachments(httpClient: HttpClient, ticket: Ticket | number, attachmentTypes: any[][], postProgress: (progress: number | string | null) => void) {
	if (typeof ticket === 'object') {
		ticket = ticket.id;
	}
	let count = 0;
	for (const attachments in attachmentTypes) {
		count += attachmentTypes[attachments].length;
	}
	sendSecureHeader(headers => {
		submitNextAttachment(httpClient, headers, ticket, attachmentTypes, 0, 0, 0, count, postProgress);
	});
}

function submitNextAttachment(httpClient: HttpClient, headers: HttpHeaders, idTicket, attachmentTypes: any[][], typeI: number, subI: number, counting: number, count: number, postProgress: (progress: number | string | null) => void) {
	const options = Object.keys(attachmentTypes);
	if (typeI >= options.length) {
		postProgress(null);
		return;
	}
	const attachType = options[typeI];
	const array: any[] = attachmentTypes[attachType];
	if (subI >= array.length) {
		submitNextAttachment(httpClient, headers, idTicket, attachmentTypes, typeI + 1, 0, counting, count, postProgress);
		return;
	}
	const attachment = array[subI];

	postProgress('attaching ' + (++counting) + ' ' + count);
	headers = headers.set('Type', 'attachment')
		.set('Attached-To', idTicket)
		.set('Attach-Type', attachType);
	httpClient.post<any>(GLOBAL.api + '/TicketForm', attachment, {headers}).toPromise()
		.then(response => {
			postProgress(40 / count);
			submitNextAttachment(httpClient, headers, idTicket, attachmentTypes, typeI, subI + 1, counting, count, postProgress);
		})
		.catch(response => {
			postProgress(40 / count);
			submitNextAttachment(httpClient, headers, idTicket, attachmentTypes, typeI, subI + 1, counting, count, postProgress);
		});
}
