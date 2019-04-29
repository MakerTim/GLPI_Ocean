import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {OpenticketPage} from './openticket.page';

describe('HomeComponent', () => {
	let component: OpenticketPage;
	let fixture: ComponentFixture<OpenticketPage>;

	beforeEach(async(() => {
		TestBed.configureTestingModule({
			declarations: [OpenticketPage]
		})
			.compileComponents();
	}));

	beforeEach(() => {
		fixture = TestBed.createComponent(OpenticketPage);
		component = fixture.componentInstance;
		fixture.detectChanges();
	});

	it('should create', () => {
		expect(component).toBeTruthy();
	});
});
