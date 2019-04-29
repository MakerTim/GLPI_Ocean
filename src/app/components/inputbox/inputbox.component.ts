/* tslint:disable:component-selector */
import {Component, ElementRef, OnInit, ViewChild} from '@angular/core';
import {HttpClient} from '@angular/common/http';

@Component({
	selector: 'inputbox',
	templateUrl: './inputbox.component.html',
	styleUrls: ['./inputbox.component.scss']
})
export class InputboxComponent implements OnInit {
	@ViewChild('preview') preview: ElementRef;
	@ViewChild('inputField') inputField: ElementRef;
	input = '';

	constructor(
		private client: HttpClient) {
	}

	ngOnInit() {
	}

	togglePreview() {
		const previewElement = this.preview.nativeElement;
		if (previewElement.style.opacity === '1') {
			previewElement.style.opacity = '0';
		} else {
			previewElement.style.opacity = '1';
		}
	}

	insertMD(mdKey: string) {
		const inputField = this.inputField.nativeElement;
		let selected = inputField.value.slice(inputField.selectionStart, inputField.selectionEnd);
		const preSpacesSelected = selected.match(/^\s*/)[0];
		const postSpacesSelected = selected.match(/\s*$/)[0];
		selected = selected.trim();
		if (selected.length >= 2
			&& selected[0] === mdKey
			&& selected[selected.length - 1] === mdKey) {
			mdKey = '';
			selected = selected.substr(1, selected.length - 2);
		}
		const lastSelectionEnd = inputField.selectionEnd + mdKey.length;
		this.input = inputField.value.slice(0, inputField.selectionStart)
			+ preSpacesSelected
			+ mdKey
			+ selected
			+ mdKey
			+ postSpacesSelected
			+ inputField.value.slice(inputField.selectionEnd);

		this.selectInputAt(lastSelectionEnd);
	}

	toggleCasing() {
		const inputField = this.inputField.nativeElement;
		let selected = inputField.value.slice(inputField.selectionStart, inputField.selectionEnd);

		if (selected === selected.toLowerCase()) {
			selected = selected.toUpperCase();
		} else if (selected === selected.toUpperCase()) {
			if (selected.length === 1) {
				selected = selected.toLowerCase();
			} else {
				selected = selected.slice(0, 1).toUpperCase() + selected.slice(1).toLowerCase();
			}
		} else {
			selected = selected.toLowerCase();
		}

		this.input = inputField.value.slice(0, inputField.selectionStart)
			+ selected
			+ inputField.value.slice(inputField.selectionEnd);

		this.selectInputAt(inputField.selectionStart, inputField.selectionEnd);
	}

	private selectInputAt(startIndex: number, endIndex: number = startIndex) {
		const inputField = this.inputField.nativeElement;
		inputField.focus();
		inputField.setSelectionRange(startIndex, endIndex);
		setTimeout(() => inputField.setSelectionRange(startIndex, endIndex), 1);
	}
}
