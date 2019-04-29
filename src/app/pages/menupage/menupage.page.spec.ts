import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {MenupagePage} from './menupage.page';

describe('HomeComponent', () => {
	let component: MenupagePage;
	let fixture: ComponentFixture<MenupagePage>;

	beforeEach(async(() => {
		TestBed.configureTestingModule({
			declarations: [MenupagePage]
		})
			.compileComponents();
	}));

	beforeEach(() => {
		fixture = TestBed.createComponent(MenupagePage);
		component = fixture.componentInstance;
		fixture.detectChanges();
	});

	it('should create', () => {
		expect(component).toBeTruthy();
	});
});
