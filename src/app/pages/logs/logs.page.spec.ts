import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {LogsPage} from './logs.page';

describe('HomeComponent', () => {
	let component: LogsPage;
	let fixture: ComponentFixture<LogsPage>;

	beforeEach(async(() => {
		TestBed.configureTestingModule({
			declarations: [LogsPage]
		})
			.compileComponents();
	}));

	beforeEach(() => {
		fixture = TestBed.createComponent(LogsPage);
		component = fixture.componentInstance;
		fixture.detectChanges();
	});

	it('should create', () => {
		expect(component).toBeTruthy();
	});
});
