<script setup>
import { ref, defineProps, onMounted, onBeforeUnmount, computed, shallowRef } from 'vue'
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import Panel from 'primevue/panel';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Popover from 'primevue/popover';
import * as echarts from 'echarts';
import { FilterMatchMode } from '@primevue/core/api';
import { useLayout } from '@/Layouts/composables/layout';

const datas = defineProps({
    routers: Object,
})

const { layoutConfig } = useLayout()
const isDarkMode = computed(() => {
    return layoutConfig.darkTheme
})

const lists = ref(Array())
const ethernet = ref(null)
const chartRefs = shallowRef([])
const chartInstances = shallowRef([])
const options = shallowRef([])

const networkDlg = ref(false)
const networkHeader = ref(null)
const tbList = ref(Array())
const loading = ref(true)
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    address: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    interface: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    network: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
})

const initData = () => {
    lists.value = []
    options.value = []
    const parsing = JSON.parse(datas.routers)
    if (parsing.length > 0) {
        parsing.map((ls) => {
            lists.value.push(ls)
            const init = initOption()
            options.value.push(init)
        })
    }
}

onMounted(() => {
    initCharts()
    lists.value.forEach((ls) => {
        if (ls) {
            console.log('list', ls)
            setUpdate(ls.id)
        }
    })
    // console.log('lists', lists.value)
})

onBeforeUnmount(() => {
    disposeCharts()
});

const test = () => {
    //
}

const setUpdate = (id) => {
    if (id > -1) {
        // console.log(id)
        axios.post('/network/graphic', {id: id}).then((response) => {
            const res = response.data
            if (res) {
                // console.log(res)
                ethernet.value = res.name
                updateInterval(res.time, res.rx, res.tx)
            }
        })
    }
}

const updateInterval = (label, rx, tx) => {
    // console.log('updating...')
    updateChart(label, rx, tx)
    chartInstances.value.forEach((instance, index) => {
        if (instance) {
        const { animationDuration, animationEasing, ...rest } = options.value[index];
        instance.setOption({
            ...rest,
            animation: true
        }, true);
        }
    });
    window.dispatchEvent(new Event('resize')); // Trigger resize to handle container changes
}


const colors = ['#5470C6', '#EE6666'];
const formatter = (bytes) => {
    const sizes = ['bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps']
    if (bytes == 0) return '0 bps'
    const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)))
    return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i]
}
const initOption = () => {
    return {
        color: colors,
        updates: 0,
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'cross'
            },
            borderWidth: 1,
            borderColor: '#ccc',
            padding: 10,
            formatter: function(params) {
                const _tx = formatter(params[0].value)
                const _rx = formatter(params[1].value)

                return (
                    '<label><u><b>' + params[0].axisValueLabel + '</b></u></label>' +
                    '<br><br>' +
                    '<table style="width: 100%">' +
                        (
                            ((parseInt(params[0].value) > parseInt(params[1].value)) || parseInt(params[0].value) === parseInt(params[1].value)) ?
                            (
                                '<tr><td style="width: 50%; text-align: left;"><label style="color: '+colors[0]+'"><b><span style="color: '+colors[0]+'; line-height: .4em; font-size: 1em;">&middot;</span> ' + params[0].seriesName + '</b></label></td> <td style="width: 50%; text-align: right;">' + _tx + '</td></tr>' +
                                '<tr><td style="width: 50%; text-align: left;"><label style="color: '+colors[1]+'"><b><span style="color: '+colors[1]+'; line-height: .4em; font-size: 1em;">&middot;</span> ' + params[1].seriesName + '</b></label></td> <td style="width: 50%; text-align: right;">' + _rx + '</td></tr>'
                            ) :
                            (
                                '<tr><td style="width: 50%; text-align: left;"><label style="color: '+colors[1]+'"><b><span style="color: '+colors[1]+'; line-height: .4em; font-size: 1em;">&middot;</span> ' + params[1].seriesName + '</b></label></td> <td style="width: 50%; text-align: right;">' + _rx + '</td></tr>' +
                                '<tr><td style="width: 50%; text-align: left;"><label style="color: '+colors[0]+'"><b><span style="color: '+colors[0]+'; line-height: .4em; font-size: 1em;">&middot;</span> ' + params[0].seriesName + '</b></label></td> <td style="width: 50%; text-align: right;">' + _tx + '</td></tr>'
                            )
                        ) +
                    '</table>'
                )
            }
        },
        legend: {},
        grid: {
            top: 70,
            bottom: 50,
            left: 75,
            right: 15,
        },
        xAxis: {
            type: 'category',
            axisTick: {
                alignWithLabel: true
            },
            axisLine: {
                onZero: false,
            },
            axisPointer: {
                label: {
                    formatter: function (params) {
                        return (
                            'Waktu : ' +
                            params.value
                        );
                    }
                }
            },
            splitLine: {
                show: false
            },
            data: []
        },
        yAxis: [
            {
                type: 'value',
                boundaryGap: [0, '100%'],
                splitLine: {
                    show: false
                },
                axisLabel: {
                    formatter: function(value, index) {
                        return formatter(value)
                    },
                },   
                axisPointer: {
                    label: {
                        formatter: function(params) {
                            return formatter(params.value)
                        }
                    }
                }
            }
        ],
        series: [
            {
                name: 'Tx',
                type: 'line',
                // stack: 'Total',
                smooth: true,
                emphasis: {
                    focus: 'series'
                },
                // areaStyle: {},
                data: []
            },
            {
                name: 'Rx',
                type: 'line',
                // stack: 'Total',
                smooth: true,
                emphasis: {
                    focus: 'series'
                },
                // areaStyle: {},
                data: []
            }
        ]
    }
}

// auto run
initData()
if (lists.value.length > 0) {
    lists.value.forEach((ls) => {
        if (ls) {
            setInterval(() => {
                setUpdate(ls.id)
            }, 8000)
        }
    })
}

const setChartRef = (el, index) => {
    // console.log(index, chartRefs.value)
    chartRefs.value[index] = el
};

const initCharts = () => {
    chartInstances.value = options.value.map((chart, index) => {
        if (chartRefs.value[index]) {
            const instance = echarts.init(chartRefs.value[index]);
            instance.setOption(chart);
            return instance;
        }
        return null;
    });
    window.addEventListener('resize', resizeCharts);
};
const resizeCharts = () => {
    chartInstances.value.forEach(instance => {
        if (instance) instance.resize();
    });
};
const disposeCharts = () => {
    window.removeEventListener('resize', resizeCharts);
    chartInstances.value.forEach(instance => {
        if (instance) instance.dispose();
    });
    chartInstances.value = [];
}

const updateChart = (label, rx, tx) => {
    const maxPoints = 10
    const sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps']
    options.value.forEach(chart => {
        chart.updates++;
        const option = chart;
        // console.log('opt', option)

        if (option.xAxis.data.length >= maxPoints) {
            option.xAxis.data.shift();
            option.series.forEach(series => series.data.shift());
        }

        // Add new time label
        option.xAxis.data.push(label)
        // const now = new Date();
        // option.xAxis.data.push(now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
        
        // Add new data points
        option.series.forEach(series => {
            const bytes = series.name === 'Tx' ? parseInt(tx) : parseInt(rx)
            let fix = 0
            if (bytes > 0) {
                const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)))
                // return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];  
                fix = parseFloat((bytes / Math.pow(1024, i)).toFixed(2))
            }
            series.data.push(bytes);
        });
    });
}

const showNetwork = (router, name) => {
    console.log(router)
    loading.value = true
    if (router.length > 0) {
        networkHeader.value = name
        tbList.value = router
        networkDlg.value = true
        loading.value = false
    } else {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Data not found!', life: 3000 });
        loading.value = false
    }
}
</script>

<template>
    <Toast />
    <ConfirmDialog></ConfirmDialog>
    <Head title="Network IP" />

    
    <Card class="w-full mb-5">
        <template #title><i class="pi pi-sitemap"></i> Data Network IP</template>
    </Card>

    <div class="w-full grid grid-cols-2 justify-between gap-4">
        <Card class="" v-for="(list, l) in lists" :key="l">
            <template #content>
                <Panel toggleable>
                    <template #header>
                        <label class="text-xl" v-if="list && list.name">{{ list.name }}</label>
                        <label class="text-xl" v-if="!list">
                            Router <Badge value="OFF" severity="danger" size="small"></Badge>
                        </label>
                    </template>

                    <div v-if="list" class="flex gap-4">
                        <div class="flex-auto">
                            <label>
                                Status is <Tag severity="success" value="ON" rounded></Tag>
                                <br>
                                Total IP Address : {{ list.data.length }}
                                <br>
                                Total assign network : 0
                            </label>
                        </div>

                        <div>
                            <Button type="button" label="Show IP Network" severity="info" icon="pi pi-eye" raised @click="showNetwork(list.data, list.name)" />
                        </div>
                    </div>
                    <div v-if="list" class="w-full">
                        <Divider>Monitoring {{ ethernet }}</Divider>
                        <div class="echart-container" >
                            <div :ref="el => setChartRef(el, l)" class="chart"></div>
                        </div>
                    </div>

                    <div v-if="!list">
                        <label class="">
                            Status is <Tag severity="danger" value="OFF" rounded></Tag>
                            <br>
                            Please turn on your router or API router.
                        </label>
                    </div>
                </Panel>
            </template>
        </Card>
    </div>

    <Dialog v-model:visible="networkDlg" modal maximizable :header="'Detail ' + networkHeader" :style="{width: '70vw'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div class="flex w-full mb-4">
            <DataTable v-model:filters="filters" :value="tbList" paginator :rows="15" :rowsPerPageOptions="[5, 10, 15, 25, 50, 100]" dataKey=".id" filterDisplay="row" :loading="loading" :globalFilterFields="['address', 'interface', 'network']" class="w-full">
                <template #header>
                    <div class="flex justify-end">
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Keyword Search" />
                        </IconField>
                    </div>
                </template>
                <template #empty> No network found. </template>
                <template #loading> Loading network data. Please wait. </template>

                <Column field="address" header="Address" style="width: 20%"></Column>
                <Column field="network" header="Network" style="width: 20%"></Column>
                <Column field="interface" header="Interface" style="width: 20%"></Column>
                <Column field="actual-interface" header="Actual" style="width: 20%"></Column>
                <Column field="invalid" header="Invalid" style="width: 15%">
                    <template #body="slotProps">
                        <Badge :value="slotProps.data.invalid" :severity="slotProps.data.invalid === 'false' ? 'danger' : 'success'"></Badge>
                    </template>
                </Column>
                <Column field="" header="" style="width: 5%">
                    <template #body="slotProps">
                        <div class="float-right">
                            <Button icon="pi pi-search" v-tooltip.bottom="'Lihat Detail'" severity="secondary" rounded />
                        </div>
                    </template>
                </Column>

                <template #paginatorcontainer="{ first, last, page, pageCount, prevPageCallback, nextPageCallback, totalRecords }">
                    <div class="flex items-center gap-4 border border-primary bg-transparent rounded-full w-full py-1 px-2 justify-between">
                        <Button icon="pi pi-chevron-left" rounded text @click="prevPageCallback" :disabled="page === 0" />
                        <div class="text-color font-medium">
                            <span class="hidden sm:block">Showing {{ first }} to {{ last }} of {{ totalRecords }}</span>
                            <span class="block sm:hidden">Page {{ page + 1 }} of {{ pageCount }}</span>
                        </div>
                        <Button icon="pi pi-chevron-right" rounded text @click="nextPageCallback" :disabled="page === pageCount - 1" />
                    </div>
                </template>
            </DataTable>
        </div>
    </Dialog>
</template>

<style scoped>
.echart-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    background-color: #030712;
    /* background-color: transparent; */
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    margin: 20px auto;
}

.chart {
    width: 100%;
    height: 500px;
    min-height: 300px;
    background-color: white;
    border-radius: 8px;
    padding: 10px;
}
</style>