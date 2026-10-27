@once
<script>
    window.regionPicker = (selected, url) => ({
        province: selected.province ?? '',
        regency: '',
        district: '',
        regencies: [],
        districts: [],
        loading: false,

        async init() {
            if (this.province) {
                await this.loadRegions(this.province, 'regencies');
                await this.$nextTick();
                this.regency = selected.regency ?? '';
            }

            if (this.regency) {
                await this.loadRegions(this.regency, 'districts');
                await this.$nextTick();
                this.district = selected.district ?? '';
            }
        },

        async loadRegions(parent, target) {
            this.loading = true;

            try {
                const response = await fetch(`${url}?parent=${encodeURIComponent(parent)}`, { headers: { Accept: 'application/json' } });
                this[target] = response.ok ? await response.json() : [];
            } catch (error) {
                this[target] = [];
            } finally {
                this.loading = false;
            }
        },

        async changeProvince() {
            this.regency = '';
            this.district = '';
            this.regencies = [];
            this.districts = [];

            if (this.province) {
                await this.loadRegions(this.province, 'regencies');
            }
        },

        async changeRegency() {
            this.district = '';
            this.districts = [];

            if (this.regency) {
                await this.loadRegions(this.regency, 'districts');
            }
        },
    });

    // Samakan penulisan "SMA NEGERI 1" dengan "SMAN 1" agar mudah dicari.
    const normalizeSchoolName = (value) => (value || '')
        .toUpperCase()
        .replace(/\b(SMA|SMK)\s+NEGERI\b/g, '$1N')
        .replace(/\b(SMA|SMK)\s+SWASTA\b/g, '$1S')
        .replace(/[^A-Z0-9]+/g, ' ')
        .trim();

    window.schoolPicker = (selected, regionsUrl, schoolsUrl, initial) => {
        const base = window.regionPicker(selected, regionsUrl);

        return {
            ...base,
            schools: [],
            available: true,
            loadingSchools: false,
            showAllDistricts: false,
            query: '',
            manual: initial.manual,
            npsn: initial.npsn ?? '',
            name: initial.name ?? '',
            type: initial.type ?? '',
            status: initial.status ?? '',

            async init() {
                await base.init.call(this);

                if (this.regency) {
                    await this.loadSchools();
                }
            },

            async changeProvince() {
                await base.changeProvince.call(this);
                this.schools = [];
            },

            async changeRegency() {
                await base.changeRegency.call(this);
                await this.loadSchools();
            },

            async loadSchools() {
                this.schools = [];

                if (! this.regency) {
                    return;
                }

                this.loadingSchools = true;

                try {
                    const response = await fetch(`${schoolsUrl}?regency=${encodeURIComponent(this.regency)}`, { headers: { Accept: 'application/json' } });
                    const data = response.ok ? await response.json() : { available: false, schools: [] };

                    this.available = data.available;
                    this.schools = data.schools.map((school) => ({ ...school, search: normalizeSchoolName(school.name) }));
                } catch (error) {
                    this.available = false;
                } finally {
                    this.loadingSchools = false;
                }
            },

            get filteredSchools() {
                const tokens = normalizeSchoolName(this.query).split(' ').filter(Boolean);

                let schools = this.schools;

                if (this.district && ! this.showAllDistricts) {
                    const inDistrict = schools.filter((school) => school.district_id === this.district);
                    schools = inDistrict.length ? inDistrict : schools;
                }

                return schools
                    .filter((school) => tokens.every((token) => school.search.includes(token) || school.npsn.startsWith(token)))
                    .slice(0, 50);
            },

            pickSchool(school) {
                this.npsn = school.npsn;
                this.name = school.name;
                this.type = school.type;
                this.status = school.status;

                if (! this.district) {
                    this.district = school.district_id;
                }
            },

            clearSchool() {
                this.npsn = '';
                this.name = '';
                this.type = '';
                this.status = '';
            },

            setManual(manual) {
                this.manual = manual;
                this.clearSchool();
            },
        };
    };
</script>
@endonce
