import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { 
    InspectorControls, 
    RichText, 
    useBlockProps 
} from '@wordpress/blockEditor';
import { 
    PanelBody, 
    SelectControl, 
    ColorPalette,
    Button 
} from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import metadata from '../../includes/pros-cons-block.json';

const getIcons = (iconSet) => {
    switch(iconSet) {
        case 'thumb': return { pro: '👍', con: '👎' };
        case 'star': return { pro: '⭐', con: '☆' };
        default: return { pro: '✔️', con: '❌' };
    }
};

registerBlockType(metadata.name, {
    edit: function({ attributes, setAttributes }) {
        const blockProps = useBlockProps({ className: 'tabai-pros-cons-wrap' });
        const { blockTitle, prosTitle, consTitle, prosList, consList, prosColor, consColor, iconSet } = attributes;
        const icons = getIcons(iconSet);

        const updateItem = (listName, list, index, newText) => {
            const newList = [...list];
            newList[index] = { ...newList[index], text: newText };
            setAttributes({ [listName]: newList });
        };

        const addItem = (listName, list) => {
            setAttributes({ [listName]: [...list, { text: '' }] });
        };

        const removeItem = (listName, list, index) => {
            const newList = [...list];
            newList.splice(index, 1);
            setAttributes({ [listName]: newList });
        };

        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title={__('Settings', 'tabaix-seo-optimizer-pro')}>
                        <SelectControl 
                            label={__('Icon Set', 'tabaix-seo-optimizer-pro')} 
                            value={iconSet} 
                            options={[
                                { label: 'Checks & Crosses', value: 'check-cross' },
                                { label: 'Thumbs Up / Down', value: 'thumb' },
                                { label: 'Stars', value: 'star' }
                            ]} 
                            onChange={(val) => setAttributes({ iconSet: val })} 
                        />
                        <p><strong>{__('Pros Color', 'tabaix-seo-optimizer-pro')}</strong></p>
                        <ColorPalette value={prosColor} onChange={(val) => setAttributes({ prosColor: val })} />
                        <p><strong>{__('Cons Color', 'tabaix-seo-optimizer-pro')}</strong></p>
                        <ColorPalette value={consColor} onChange={(val) => setAttributes({ consColor: val })} />
                    </PanelBody>
                </InspectorControls>
                
                <div { ...blockProps }>
                    <RichText 
                        tagName="h2" 
                        value={blockTitle} 
                        onChange={(val) => setAttributes({ blockTitle: val })} 
                        placeholder={__('Pros & Cons Title', 'tabaix-seo-optimizer-pro')} 
                        style={{ textAlign: 'center', marginBottom: '20px' }} 
                    />
                    <div className="tabai-columns">
                        <div className="tabai-pros-card" style={{ '--pros-color': prosColor }}>
                            <RichText 
                                tagName="h3" 
                                value={prosTitle} 
                                onChange={(val) => setAttributes({ prosTitle: val })} 
                                placeholder={__('Pros Title', 'tabaix-seo-optimizer-pro')} 
                                style={{ color: prosColor }} 
                            />
                            <ul>
                                {prosList.map((item, index) => (
                                    <li key={index} className="tabai-item-row">
                                        <span className="tabai-icon">{icons.pro}</span>
                                        <RichText 
                                            tagName="span" 
                                            value={item.text} 
                                            onChange={(val) => updateItem('prosList', prosList, index, val)} 
                                            placeholder={__('Add pro...', 'tabaix-seo-optimizer-pro')} 
                                            style={{ flex: 1 }} 
                                        />
                                        <button className="tabai-item-delete" onClick={() => removeItem('prosList', prosList, index)} aria-label="Delete item">×</button>
                                    </li>
                                ))}
                            </ul>
                            <Button className="tabai-add-button" onClick={() => addItem('prosList', prosList)}>+ {__('Add Pro', 'tabaix-seo-optimizer-pro')}</Button>
                        </div>
                        
                        <div className="tabai-cons-card" style={{ '--cons-color': consColor }}>
                            <RichText 
                                tagName="h3" 
                                value={consTitle} 
                                onChange={(val) => setAttributes({ consTitle: val })} 
                                placeholder={__('Cons Title', 'tabaix-seo-optimizer-pro')} 
                                style={{ color: consColor }} 
                            />
                            <ul>
                                {consList.map((item, index) => (
                                    <li key={index} className="tabai-item-row">
                                        <span className="tabai-icon">{icons.con}</span>
                                        <RichText 
                                            tagName="span" 
                                            value={item.text} 
                                            onChange={(val) => updateItem('consList', consList, index, val)} 
                                            placeholder={__('Add con...', 'tabaix-seo-optimizer-pro')} 
                                            style={{ flex: 1 }} 
                                        />
                                        <button className="tabai-item-delete" onClick={() => removeItem('consList', consList, index)} aria-label="Delete item">×</button>
                                    </li>
                                ))}
                            </ul>
                            <Button className="tabai-add-button" onClick={() => addItem('consList', consList)}>+ {__('Add Con', 'tabaix-seo-optimizer-pro')}</Button>
                        </div>
                    </div>
                </div>
            </Fragment>
        );
    },
    save: function({ attributes }) {
        const blockProps = useBlockProps.save({ className: 'tabai-pros-cons-wrap' });
        const { blockTitle, prosTitle, consTitle, prosList, consList, prosColor, consColor, iconSet } = attributes;
        const icons = getIcons(iconSet);

        return (
            <div { ...blockProps }>
                <RichText.Content tagName="h2" value={blockTitle} />
                <div className="tabai-columns">
                    <div className="tabai-pros-card" style={{ '--pros-color': prosColor }}>
                        <RichText.Content tagName="h3" value={prosTitle} style={{ color: prosColor }} />
                        <ul>
                            {prosList.map((item, index) => (
                                <li key={index}>
                                    <span className="tabai-icon">{icons.pro}</span>
                                    <RichText.Content tagName="span" value={item.text} />
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="tabai-cons-card" style={{ '--cons-color': consColor }}>
                        <RichText.Content tagName="h3" value={consTitle} style={{ color: consColor }} />
                        <ul>
                            {consList.map((item, index) => (
                                <li key={index}>
                                    <span className="tabai-icon">{icons.con}</span>
                                    <RichText.Content tagName="span" value={item.text} />
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        );
    }
});
