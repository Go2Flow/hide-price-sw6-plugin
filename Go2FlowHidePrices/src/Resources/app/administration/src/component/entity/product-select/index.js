const { Component } = Shopware;
const { EntityCollection, Criteria } = Shopware.Data;
Component.extend('g2f-hp-product-select', 'sw-entity-multi-id-select', {
    inject: ['feature', 'repositoryFactory'],
    props: {
        repository: {
            type: Object,
            required: false,
            default() {
                return this.repositoryFactory.create('product');
            }
        },
    },
    methods: {
        createdComponent() {
            this.$attrs.labelProperty = ['name', 'productNumber'];
            const collection = new EntityCollection(
                this.repository.route,
                this.repository.entityName,
                this.context,
            );

            if (this.collection === null) {
                this.collection = collection;
            }

            if (this.ids.length <= 0) {
                this.collection = collection;
                return Promise.resolve(this.collection);
            }

            const criteria = Criteria.fromCriteria(this.criteria);
            criteria.setIds(this.ids);
            criteria.setTerm('');
            criteria.queries = [];

            return this.repository.search(criteria, { ...this.context, inheritance: true }).then((entities) => {
                this.collection = entities;

                if (!this.collection.length && this.ids.length) {
                    this.updateIds(this.collection);
                }

                return this.collection;
            });
        },
    }
});