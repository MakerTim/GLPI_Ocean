/* tslint:disable */
import {Component, ElementRef, Input, ViewChild} from '@angular/core';
import {HttpClient} from '@angular/common/http';

@Component({
	selector: 'customswitch',
	templateUrl: './switch.component.html',
	styleUrls: ['./switch.component.scss']
})
export class SwitchComponent {

	public rng = Math.ceil(Math.random() * 100000000);

	@ViewChild('element') element: ElementRef<Element>;

	@Input() public left = 'app.off';
	@Input() public right = 'app.on';
	@Input() public clss = '';
	@Input() public leftStyle = '';
	@Input() public rightStyle = '';
	@Input() public checked = false;
	@Input() public onChange: (value: boolean, target: Element) => void = (b, t) => console.log(b, t);

	constructor() {
	}

	markChange(event: any) {
		this.checked = event.target.checked;
		this.onChange(this.checked, this.element.nativeElement);
	}

	onSwipe(event: any) {
		const type: 'swipeleft' | 'swiperight' = event.type;
		const newChecked = type === 'swiperight';
		if (newChecked !== this.checked) {
			this.checked = newChecked;
			this.onChange(this.checked, this.element.nativeElement);
		}
	}
}

