import {Pipe, PipeTransform} from '@angular/core';

@Pipe({name: 'debug'})
export class Debug implements PipeTransform {

	constructor() {
	}

	transform(input: any): string {
		if (input === null || input === undefined) {
			return '(null)';
		}
		try {
			return JSON.stringify(input, undefined, 1) + ' (' + (typeof input) + ')';
		} catch (e) {
			console.log(input);
		}
		return '(' + (typeof input) + ') see console for details';
	}
}

@Pipe({name: 'dump'})
export class Dump implements PipeTransform {

	constructor() {
	}

	transform(input: any): string {
		if (input === null || input === undefined) {
			return '(null)';
		}
		if ((typeof input) !== 'object') {
			return input;
		}
		let dump = '';
		for (const property of Object.keys(input)) {
			dump += property + ':' + JSON.stringify(input[property]) + ' \n';
		}
		return dump;
	}
}

@Pipe({name: 'br'})
export class BR implements PipeTransform {

	constructor() {
	}

	transform(input: string): string {
		if (input === null || input === undefined) {
			return '';
		}
		return input.replace(/\n/g, '<br>');
	}
}
