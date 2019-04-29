import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {DashboardpagePage} from './dashboardmenu.page';

describe('HomeComponent', () => {
	let component: DashboardpagePage;
	let fixture: ComponentFixture<DashboardpagePage>;

	beforeEach(async(() => {
		TestBed.configureTestingModule({
			declarations: [DashboardpagePage]
		})
			.compileComponents();
	}));

	beforeEach(() => {
		fixture = TestBed.createComponent(DashboardpagePage);
		component = fixture.componentInstance;
		fixture.detectChanges();
	});

	it('should create', () => {
		expect(component).toBeTruthy();
	});
});
