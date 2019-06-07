/* tslint:disable:forin */
import {ChangeDetectorRef, Component, HostListener, OnInit, ViewChild} from '@angular/core';
import {AppComponent} from '../app.component';
import {GLOBAL} from '../GLOBAL';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {ChartDataSets, ChartOptions, ChartType} from 'chart.js';
import {ActivatedRoute} from '@angular/router';
// @ts-ignore
import ChartDataLabels from 'chartjs-plugin-datalabels';

@Component({
    selector: 'app-report',
    templateUrl: './report.component.html',
    styleUrls: ['./report.component.scss']
})
export class ReportComponent implements OnInit {

    static readonly header: string[] = ['id', 'category', 'created', 'title', 'status', 'machine', 'priority',
        'owner', 'submitter', 'modified', 'referring_tickets', 'related_tickets', 'hd_queue_id'];
    static readonly none = 'None';

    loggedIn: string = localStorage.getItem(AppComponent.loggedInKey);
    filter: string;

    queues: any;
    queueNames: string[];
    queueStates: string[];
    queueStatesData: number[][][];
    queueUsersFrom: string[][];
    queueUsersFromData: ChartDataSets[][];
    queueQuotaData: ChartDataSets[];
    queueQueuedCategory: string[][];
    queueQueuedCategoryData: number[][];
    data: string[][][];
    selectedData: number;

    piePlugins = [ChartDataLabels];
    pieOptions: (ChartOptions & { plugins: any }) = {
        legend: {position: 'left'},
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    stepSize: 2
                }
            }]
        },
        plugins: {
            datalabels: {
                color: 'rgba(0,0,0,0.6)',
                formatter(value) {
                    return value === 0 ? '' : value;
                }
            }
        }
    };
    pieOptions2: (ChartOptions & { plugins: any }) = {
        ...this.pieOptions,
        legend: {
            display: false
        }
    };
    settings = {
        autoColumnSize: false,
        colWidths(index) {
            return (window.innerWidth / ReportComponent.header.length) - (ReportComponent.header.length / 2);
        },
        colHeaders: false,
    };
    loading = true;

    public doughnutChartType: ChartType = 'bar';

    @ViewChild('hotTableComponent') hot;
    @ViewChild('chartHolders') chartHolders;
    refreshData = true;

    constructor(private http: HttpClient,
                private route: ActivatedRoute,
                private cd: ChangeDetectorRef) {
    }

    @HostListener('window:beforeprint', ['$event'])
    onBeforePrint(event) {
        this.chartHolders.nativeElement.children.forEach(div => {
            const img = div.querySelector('img');
            const canvas = div.querySelector('canvas');
            img.src = canvas.toDataURL();
            img.classList.remove('hidden');
            canvas.classList.add('hidden');
        });
        document.querySelectorAll('col').forEach(col => {
            col.style.width = '75px';
        });
    }

    @HostListener('window:afterprint', ['$event'])
    onAfterPrint() {
        this.chartHolders.nativeElement.children.forEach(div => {
            const img = div.querySelector('img');
            const canvas = div.querySelector('canvas');
            img.src = canvas.toDataURL();
            img.classList.add('hidden');
            canvas.classList.remove('hidden');
        });
        document.querySelectorAll('col').forEach(col => {
            col.style.width = '';
        });
    }

    ngOnInit() {
        this.route.queryParams.subscribe(params => {
            this.filter = params.filter ? params.filter : '';

            if (this.filter) {
                this.filter = 'ticket.' + this.filter;
                if (!this.filter.match(/^([&|]*ticket(\.(\w+))+[><=]+'.+?')+$/)) {
                    console.error('FAILED CODE INJECTION: ' + this.filter);
                    this.filter = '';
                }
            }

            this.resetData();
            this.loadQueues();
        });
    }

    resetData() {
        this.queues = {};
        this.queueNames = [];
        this.queueStates = [];
        this.queueStatesData = [[[]]];
        this.queueUsersFrom = [[ReportComponent.none]];
        this.queueUsersFromData = [[{data: [0], label: 'Owner'}, {data: [0], label: 'Submitter'}]];
        this.queueQuotaData = [];
        this.queueQueuedCategory = [];
        this.queueQueuedCategoryData = [];
        this.data = [[ReportComponent.header]];
        this.selectedData = 0;
    }

    loadQueues() {
        this.loading = true;
        const headers = new HttpHeaders().set('x-dell-api-version', '1').set('x-dell-csrf-token', this.loggedIn);
        this.http.get(GLOBAL.kaceURL + 'api/service_desk/queues/', {headers, withCredentials: true}).subscribe((response: any) => {
            if (response.error || response.errorCode) {
                this.loggedIn = null;
                return;
            }
            response.Queues.forEach(queue => {
                const queueData = [ReportComponent.header];
                const queueStatusData = [[]];
                const queueUsersData = [{data: [0], label: 'Owner'}, {data: [0], label: 'Submitter'}];
                this.data.push(queueData);
                this.queueNames.push(queue.name);

                this.queueStatesData.push(queueStatusData);
                this.queueUsersFrom.push([ReportComponent.none]);
                this.queueUsersFromData.push(queueUsersData);
                this.queueQuotaData.push({data: [0, 0], label: queue.name});

                this.queueQueuedCategory.push([]);
                this.queueQueuedCategoryData.push([]);

                this.queues[queue.id] = [queue.name, queueData, queueStatusData];
            });
            this.loadTicketsFromQueue();
        });
    }

    loadTicketsFromQueue() {
        const headers = new HttpHeaders().set('x-dell-api-version', '1').set('x-dell-csrf-token', this.loggedIn);
        this.http.get(GLOBAL.kaceURL + 'api/service_desk/tickets/' +
            '?shaping=owner all,submitter all,queue all,category all,priority all,' +
            'status all,machine all,asset all,related_tickets all,referring_tickets all' +
            '&paging=limit ALL', {
            headers, withCredentials: true
        }).subscribe((response: any) => {
            if (response.error || response.errorCode) {
                this.loggedIn = null;
                return;
            }
            response.Tickets.forEach(ticket => {
                const queue = this.queues[ticket.hd_queue_id];
                const queueIndex = this.queueNames.indexOf(queue[0]);
                ticket.hd_queue_id = queue[0];
                ticket._category = ticket.category;
                ticket.category = ticket.category ? ticket.category.name : '';
                ticket._owner = ticket.owner;
                ticket.owner = ticket.owner ? ticket.owner.full_name : ReportComponent.none;
                ticket._priority = ticket.priority;
                ticket.priority = ticket.priority ? ticket.priority.name : '';
                ticket._status = ticket.status;
                ticket.status = ticket.status ? ticket.status.state : '';
                ticket._submitter = ticket.submitter;
                ticket.submitter = ticket.submitter ? ticket.submitter.full_name : '';
                ticket._machine = ticket.machine;
                ticket.machine = ticket.machine ? ticket.machine.name : '';
                ticket._referring_tickets = ticket.referring_tickets;
                ticket.referring_tickets = ticket.referring_tickets.length;
                ticket._related_tickets = ticket.related_tickets;
                ticket.related_tickets = ticket.related_tickets.length;

                if (this.filterTicket(ticket)) {
                    return;
                }

                // @ts-ignore
                (this.queueQuotaData[queueIndex].data)[0]++;

                let statusIndex = this.queueStates.indexOf(ticket.status);
                if (statusIndex === -1) {
                    statusIndex = this.queueStates.length;
                    this.queueStates.push(ticket.status);
                    this.queueStatesData.forEach(data => {
                        data[0].push(0);
                    });
                }
                this.queueStatesData[0][0][statusIndex]++;
                this.queueStatesData[queueIndex + 1][0][statusIndex]++;

                const userList = [ticket.owner.substr(0, 10), ticket.submitter.substr(0, 10)];
                for (const i in userList) {
                    let userIndexGlobal = this.queueUsersFrom[0].indexOf(userList[i]);
                    if (userIndexGlobal === -1) {
                        userIndexGlobal = 1;
                        this.queueUsersFrom[0].splice(1, 0, userList[i]);
                        for (const j in userList) {
                            this.queueUsersFromData[0][j].data.splice(1, 0, 0);
                        }
                    }
                    // @ts-ignore
                    this.queueUsersFromData[0][i].data[userIndexGlobal]++;

                    let userIndexQueue = this.queueUsersFrom[queueIndex + 1].indexOf(userList[i]);
                    if (userIndexQueue === -1) {
                        userIndexQueue = this.queueUsersFrom[queueIndex + 1].length;
                        this.queueUsersFrom[queueIndex + 1].push(userList[i]);
                        for (const j in userList) {
                            // @ts-ignore
                            this.queueUsersFromData[queueIndex + 1][j].data.push(0);
                        }
                    }
                    // @ts-ignore
                    this.queueUsersFromData[queueIndex + 1][i].data[userIndexQueue]++;
                }

                let categoryIndex = this.queueQueuedCategory[queueIndex].indexOf(ticket.category);
                if (categoryIndex === -1) {
                    categoryIndex = this.queueQueuedCategory[queueIndex].length;
                    this.queueQueuedCategory[queueIndex].push(ticket.category);
                    this.queueQueuedCategoryData[queueIndex].push(0);
                }
                this.queueQueuedCategoryData[queueIndex][categoryIndex]++;

                const ticketArray = [];
                ReportComponent.header.forEach(header => {
                    ticketArray.push(ticket[header]);
                });
                this.data[0].push(ticketArray);
                queue[1].push(ticketArray);
            });
            this.cd.detectChanges();
            this.postData();
            this.loading = false;
        });
    }

    loadFromData(ticket, headers) {
        // TODO: return submitter label; for now return submitter name
        const response = this.http.get(GLOBAL.kaceURL + 'api/users/' + ticket._submitter.id + '/permissions/', {
            headers,
            withCredentials: true
        }).toPromise();

        console.log(response);
        return ticket.submitter;
    }

    filterTicket(ticket: any) {
        if (!this.filter) {
            return false;
        }
        // tslint:disable-next-line:no-eval
        return !eval(this.filter);
    }

    postData() {
        this.refreshData = false;
        setTimeout(() => {
            this.refreshData = true;
        }, 1);
    }
}
