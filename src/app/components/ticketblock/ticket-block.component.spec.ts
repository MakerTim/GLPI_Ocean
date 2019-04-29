import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TicketBlockComponent } from './ticket-block.component';

describe('TicketBlockComponent', () => {
  let component: TicketBlockComponent;
  let fixture: ComponentFixture<TicketBlockComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TicketBlockComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TicketBlockComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
