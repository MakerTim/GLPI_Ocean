import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {ListallPage} from './listall.page';

describe('HomeComponent', () => {
	let component: ListallPage;
	let fixture: ComponentFixture<ListallPage>;

	beforeEach(async(() => {
		TestBed.configureTestingModule({
			declarations: [ListallPage]
		})
			.compileComponents();
	}));

	beforeEach(() => {
		fixture = TestBed.createComponent(ListallPage);
		component = fixture.componentInstance;
		fixture.detectChanges();
	});

	it('should create', () => {
		expect(component).toBeTruthy();
	});
});
